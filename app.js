(function () {
  "use strict";

  var state = { events: [], categories: [], athletes: [], cities: [], marks: [], counts: {}, ranking: null, rankingVisible: {}, history: null, historyVisible: {}, historyArea: "", currentUser: null, editableAthleteIds: [], editingMarkId: null };

  function byId(id) { return document.getElementById(id); }
  function t(value) { return window.RankingI18n.t(value); }
  function areaLabel(area) { return { pista_cubierta: "Pista Cubierta", aire_libre: "Aire Libre", ruta: "Ruta" }[area] || area; }
  function groupLabel(group) { return t(normalized(group).replace("ç", "c") === "llancaments" ? "Llançaments" : group); }
  function eventLabel(event) { return t(areaLabel(event.area)) + " / " + groupLabel(event.eventGroup) + " / " + t(event.name || event.event || ""); }
  function eventShortLabel(event) { return groupLabel(event.eventGroup) + " / " + t(event.name || event.event || ""); }
  function categoryLabel(value) { var parts = String(value || "").split(" - "); parts[0] = parts[0].replace(/^Master /, t("Master") + " "); if (parts[0] === "Senior") parts[0] = t("Senior"); if (parts[1]) parts[1] = t(parts[1]); return parts.join(" - "); }
  function sexLabel(value) { return value === "femenino" ? t("Ranking Femenino") : t("Ranking Masculino"); }
  function categorySex(category) { return normalized(String(category).split(" - ")[1] || "") === "femenino" ? "femenino" : "masculino"; }
  function categoryBase(category) { return String(category || "").split(" - ")[0]; }
  function categoryTabLabel(category) { return categoryLabel(categoryBase(category)); }
  function normalized(value) { return String(value || "").trim().toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, ""); }
  function raceDistance(event) { var name = normalized(event.name || event.event).replace(",", "."), match, group = normalized(event.eventGroup); if (group !== "curses" && group !== "tanques") return null; if (name === "milla") return 1609; if (name.indexOf("mitja") === 0) return 21097; if (name === "marato") return 42195; match = /^(\d+(?:\.\d+)?)\s*(km)?/.exec(name); return match ? Number(match[1]) * (match[2] ? 1000 : 1) : null; }
  function compareEvents(first, second) { var firstDistance = raceDistance(first), secondDistance = raceDistance(second); if (firstDistance !== null && secondDistance !== null) return firstDistance - secondDistance; return t(first.name || first.event).localeCompare(t(second.name || second.event)); }
  function eventRank(id) {
    var ordered = state.events.slice().sort(function (first, second) {
      var areaOrder = ["pista_cubierta", "aire_libre", "ruta"];
      var area = areaOrder.indexOf(first.area) - areaOrder.indexOf(second.area);
      var group = groupLabel(first.eventGroup).localeCompare(groupLabel(second.eventGroup));
      return area || group || compareEvents(first, second);
    });
    for (var index = 0; index < ordered.length; index += 1) if (String(ordered[index].id) === String(id)) return index;
    return 9999;
  }
  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }
  function detailLine(value, label) { return value ? '<span class="cell-detail">' + (label ? '<span class="cell-detail-label">' + escapeHtml(label) + ':</span> ' : "") + escapeHtml(value) + '</span>' : ""; }
  function technicalValue(value) { return normalized(value) === "no" ? "" : String(value || ""); }
  function technicalDetail(value) { return detailLine(technicalValue(value), t("Marca técnica")); }
  function inlineField(label, input) { return '<label class="inline-edit-field"><span>' + escapeHtml(label) + '</span>' + input + '</label>'; }
  function isPublicAdmin() { return state.currentUser && state.currentUser.role === "admin"; }
  function hasPublicEditAccess() { return isPublicAdmin() || state.editableAthleteIds.length > 0; }
  function canEditPublicMark(mark) { return isPublicAdmin() || state.editableAthleteIds.indexOf(String(mark.athleteId)) !== -1; }
  function eventCell(mark) { return escapeHtml(eventLabel(mark)); }
  function cityLabel(city) { return city.name + (city.province ? " (" + city.province + ")" : ""); }
  function selectedPublicCity(value) {
    var query = normalized(value);
    return state.cities.find(function (city) { return normalized(cityLabel(city)) === query || normalized(city.name) === query; });
  }
  function publicCityId(mark, value) {
    if (normalized(value) === normalized(cityInputValue(mark)) || normalized(value) === normalized(mark.city)) return mark.cityId;
    var city = selectedPublicCity(value);
    return city ? city.id : null;
  }
  function cityInputValue(mark) {
    var current = state.cities.find(function (city) { return String(city.id) === String(mark.cityId); });
    return current ? cityLabel(current) : (mark.city || "");
  }
  function cityCell(mark) {
    if (canEditPublicMark(mark) && isEditingMark(mark)) {
      return inlineField(t("Ciudad"), '<input class="inline-mark-input inline-city-input" data-public-edit-city list="public-cities" value="' + escapeHtml(cityInputValue(mark)) + '" aria-label="' + escapeHtml(t("Ciudad")) + '">') +
        inlineField(t("Nombre de la pista de atletismo"), '<input class="inline-mark-input inline-track-input" data-public-edit-track value="' + escapeHtml(mark.trackName || "") + '" aria-label="' + escapeHtml(t("Nombre de la pista de atletismo")) + '">');
    }
    return escapeHtml(mark.city) + detailLine(mark.trackName);
  }
  function publicActionHeader() { return hasPublicEditAccess() ? "<th></th>" : ""; }
  function isEditingMark(mark) { return state.editingMarkId && String(state.editingMarkId) === String(mark.id); }
  function normalizeResultText(value) {
    var result = String(value == null ? "" : value).trim();
    var hourMatch = /^(\d+)[hH](\d{1,2})['’](\d{1,2})"(\d{0,2})$/.exec(result);
    if (hourMatch) {
      var hourDecimal = hourMatch[4] || "0";
      hourDecimal = hourDecimal.length === 1 ? hourDecimal + "0" : hourDecimal.slice(0, 2);
      return String(Number(hourMatch[1])) + ":" + String(Number(hourMatch[2])).padStart(2, "0") + ":" + String(Number(hourMatch[3])).padStart(2, "0") + "." + hourDecimal.padStart(2, "0");
    }
    var minuteMatch = /^(\d+)['’](\d{1,2})"(\d{0,2})$/.exec(result);
    if (minuteMatch) {
      var minuteDecimal = minuteMatch[3] || "0";
      minuteDecimal = minuteDecimal.length === 1 ? minuteDecimal + "0" : minuteDecimal.slice(0, 2);
      return String(Number(minuteMatch[1])) + ":" + String(Number(minuteMatch[2])).padStart(2, "0") + "." + minuteDecimal.padStart(2, "0");
    }
    var quotedMinuteMatch = /^(\d+)"(\d{1,2})"$/.exec(result);
    if (quotedMinuteMatch) return String(Number(quotedMinuteMatch[1])) + ":" + String(Number(quotedMinuteMatch[2])).padStart(2, "0") + ".00";
    var secondMatch = /^(\d+)"(\d{1,2})$/.exec(result);
    if (secondMatch) return "00:" + String(Number(secondMatch[1])).padStart(2, "0") + "." + secondMatch[2];
    if (/^\d+,\d+$/.test(result)) return result.replace(",", ".");
    return result;
  }
  function displayResult(value) { return normalizeResultText(value); }
  function resultCell(mark, strong) {
    if (canEditPublicMark(mark) && isEditingMark(mark)) {
      return inlineField(t("Marca"), '<input class="inline-mark-input" data-public-edit-result value="' + escapeHtml(displayResult(mark.result)) + '" aria-label="' + escapeHtml(t("Marca")) + '">') +
        inlineField(t("Marca técnica"), '<input class="inline-mark-input inline-technical-input" data-public-edit-technical value="' + escapeHtml(technicalValue(mark.technicalInfo)) + '" aria-label="' + escapeHtml(t("Marca técnica")) + '">');
    }
    var result = strong ? '<strong>' + escapeHtml(displayResult(mark.result)) + '</strong>' : escapeHtml(displayResult(mark.result));
    return result + technicalDetail(mark.technicalInfo);
  }
  function dateCell(mark) {
    if (canEditPublicMark(mark) && isEditingMark(mark)) return '<input class="inline-mark-input" type="date" data-public-edit-date value="' + escapeHtml(mark.date || "") + '" aria-label="' + escapeHtml(t("Fecha")) + '">';
    return formatDate(mark.date);
  }
  function publicActions(mark) {
    if (!hasPublicEditAccess()) return "";
    if (!mark.id || !canEditPublicMark(mark)) return "<td></td>";
    if (isEditingMark(mark)) {
      return '<td class="public-actions"><button class="public-action-button" type="button" data-public-save-mark="' + escapeHtml(mark.id) + '" title="' + escapeHtml(t("Guardar")) + '">' + escapeHtml(t("Guardar")) + '</button><button class="public-action-button" type="button" data-public-cancel-edit="' + escapeHtml(mark.id) + '" title="' + escapeHtml(t("Cancelar")) + '">' + escapeHtml(t("Cancelar")) + '</button></td>';
    }
    return '<td class="public-actions"><button class="public-action-button" type="button" data-public-edit-mark="' + escapeHtml(mark.id) + '" title="' + escapeHtml(t("Editar")) + '">' + escapeHtml(t("Editar")) + '</button><button class="public-action-button danger" type="button" data-public-delete-mark="' + escapeHtml(mark.id) + '" title="' + escapeHtml(t("Eliminar")) + '">' + escapeHtml(t("Eliminar")) + '</button></td>';
  }
  function unique(values) { return values.filter(function (value, index) { return values.indexOf(value) === index; }); }
  function formatDate(value) {
    if (!value) return "";
    var locale = window.RankingI18n.language() === "ca" ? "ca-ES" : "es-ES";
    return new Date(value + "T00:00:00").toLocaleDateString(locale);
  }
  function resultValue(result) {
    var clean = normalizeResultText(result).replace(",", ".").replace(/[^\d:.]/g, "");
    var parts = clean.split(":").map(Number);
    if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
    if (parts.length === 2) return parts[0] * 60 + parts[1];
    return parts[0] || 0;
  }
  function compareHistory(first, second) {
    var difference = resultValue(first.result) - resultValue(second.result);
    if (first.resultDirection === "higher") difference *= -1;
    return difference || String(second.date).localeCompare(String(first.date));
  }
  function categoryRank(category) {
    var names = ["Sub8", "Sub10", "Sub12", "Sub14", "Sub16", "Sub18", "Sub20", "Sub23", "Senior"];
    var base = String(category).split(" - ")[0];
    var position = names.indexOf(base);
    if (position >= 0) return position;
    var master = /Master (\d+)/.exec(base);
    return master ? 10 + Number(master[1]) : 999;
  }
  function showError(message) {
    byId("public-error").textContent = t(message || "");
    byId("public-error").classList.toggle("hidden", !message);
  }
  function setUrl(params, replace) {
    var query = new URLSearchParams();
    Object.keys(params).forEach(function (key) {
      if (params[key]) query.set(key, params[key]);
    });
    var next = window.location.pathname + (query.toString() ? "?" + query.toString() : "");
    if (next !== window.location.pathname + window.location.search) {
      window.history[replace ? "replaceState" : "pushState"]({}, "", next);
    }
  }
  function currentFilterParams() {
    return {
      area: byId("area-filter").value,
      group: byId("group-filter").value,
      event: byId("event-filter").value,
      category: byId("category-filter").value,
      sex: byId("sex-filter").value
    };
  }
  function clearUrl(replace) { setUrl({}, replace); }

  function renderSearch() {
    byId("public-athletes").innerHTML = state.athletes.map(function (athlete) {
      return '<option value="' + escapeHtml(athlete.name) + '"></option>';
    }).join("");
    if (byId("public-cities")) byId("public-cities").innerHTML = state.cities.map(function (city) {
      return '<option value="' + escapeHtml(cityLabel(city)) + '"></option>';
    }).join("");
  }

  function filterButton(level, value, label, active) {
    return '<button class="filter-tab' + (active ? ' active' : '') + '" type="button" data-filter-level="' + level + '" data-filter-value="' + escapeHtml(value) + '" aria-pressed="' + (active ? 'true' : 'false') + '">' + escapeHtml(label) + '</button>';
  }
  function filterCard(kind, contents) { return '<section class="filter-card filter-card--' + kind + '"><div class="filter-tab-list">' + contents + '</div></section>'; }
  function groupsForArea(area) {
    return unique(state.events.filter(function (event) { return event.area === area; }).map(function (event) { return event.eventGroup; })).sort(function (first, second) {
      return groupLabel(first).localeCompare(groupLabel(second));
    });
  }
  function eventsForGroup(area, group) {
    return state.events.filter(function (event) { return event.area === area && event.eventGroup === group; }).sort(compareEvents);
  }
  function firstGroupForArea(area) { return groupsForArea(area)[0] || ""; }
  function firstEventForGroup(area, group) { var events = eventsForGroup(area, group); return events.length ? String(events[0].id) : ""; }
  function rankingGroupsForArea(area) {
    var groups = state.ranking ? state.ranking.groups : [];
    return unique(groups.filter(function (grouped) { return grouped.event.area === area; }).map(function (grouped) { return grouped.event.eventGroup; })).sort(function (first, second) {
      return groupLabel(first).localeCompare(groupLabel(second));
    });
  }
  function rankingEventsForGroup(area, group) {
    var seen = [];
    var groups = state.ranking ? state.ranking.groups : [];
    return groups.filter(function (grouped) { return grouped.event.area === area && grouped.event.eventGroup === group; }).map(function (grouped) { return grouped.event; }).filter(function (event) {
      var id = String(event.id);
      if (seen.indexOf(id) !== -1) return false;
      seen.push(id);
      return true;
    }).sort(compareEvents);
  }
  function firstRankingGroupForArea(area) { return rankingGroupsForArea(area)[0] || firstGroupForArea(area); }
  function firstRankingEventForGroup(area, group) { var events = rankingEventsForGroup(area, group); return events.length ? String(events[0].id) : firstEventForGroup(area, group); }
  function availableEvents() { return state.ranking && state.ranking.available ? state.ranking.available : []; }
  function tabAreas() {
    return unique(availableEvents().map(function (event) { return event.area; })).sort(function (first, second) { return areaRank(first) - areaRank(second); });
  }
  function tabGroups(area) {
    return unique(availableEvents().filter(function (event) { return event.area === area; }).map(function (event) { return event.eventGroup; })).sort(function (first, second) {
      return groupLabel(first).localeCompare(groupLabel(second));
    });
  }
  function tabEvents(area, group) { return availableEvents().filter(function (event) { return event.area === area && event.eventGroup === group; }).sort(compareEvents); }
  function categoryTabsForSex(sex) {
    return state.categories.filter(function (category) { return categorySex(category) === sex; }).sort(function (first, second) {
      return categoryRank(first) - categoryRank(second) || first.localeCompare(second);
    });
  }
  function firstTabGroupForArea(area) { return tabGroups(area)[0] || firstGroupForArea(area); }
  function firstTabEventForGroup(area, group) { var events = tabEvents(area, group); return events.length ? String(events[0].id) : firstEventForGroup(area, group); }
  function renderFilterTabs() {
    var sex = byId("sex-filter").value;
    var category = byId("category-filter").value;
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    var rows = [];
    rows.push(filterCard("sex", ["masculino", "femenino"].map(function (item) {
      return filterButton("sex", item, sexLabel(item), item === sex);
    }).join("")));
    if (sex) {
      var categories = categoryTabsForSex(sex);
      if (categories.length) {
        rows.push(filterCard("category", categories.map(function (item) {
          return filterButton("category", item, categoryTabLabel(item), item === category);
        }).join("")));
      }
      var areas = tabAreas();
      if (areas.length) {
        rows.push(filterCard("area", areas.map(function (item) {
          return filterButton("area", item, t(areaLabel(item)), item === area);
        }).join("")));
      }
      if (area) {
        var groups = tabGroups(area);
        if (groups.length) rows.push(filterCard("group", groups.map(function (item) {
          return filterButton("group", item, groupLabel(item), item === group);
        }).join("")));
      }
      if (area && group) {
        var events = tabEvents(area, group);
        if (events.length) rows.push(filterCard("event", events.map(function (event) {
          return filterButton("event", event.id, t(event.name), String(event.id) === eventId);
        }).join("")));
      }
    }
    byId("filter-tabs").innerHTML = rows.join("");
  }
  function renderFilters() {
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    var sex = byId("sex-filter").value;
    var areas = unique(state.events.map(function (event) { return event.area; }));
    if (!areas.includes(area)) area = "";
    var groups = area ? groupsForArea(area) : [];
    if (!groups.includes(group)) group = "";
    var events = group ? eventsForGroup(area, group) : [];
    if (!events.some(function (event) { return String(event.id) === eventId; })) eventId = "";
    byId("area-filter").innerHTML = '<option value="">' + t("Todos los ámbitos") + '</option>' + areas.map(function (item) {
      return '<option value="' + item + '">' + escapeHtml(t(areaLabel(item))) + '</option>';
    }).join("");
    byId("group-filter").innerHTML = '<option value="">' + t("Todos los grupos") + '</option>' + groups.map(function (item) {
      return '<option value="' + escapeHtml(item) + '">' + escapeHtml(groupLabel(item)) + '</option>';
    }).join("");
    byId("event-filter").innerHTML = '<option value="">' + t("Todas las pruebas") + '</option>' + events.map(function (event) {
      return '<option value="' + event.id + '">' + escapeHtml(t(event.name)) + '</option>';
    }).join("");
    byId("category-filter").innerHTML = '<option value="">' + t("Todas las categorias") + '</option>' + state.categories.slice().sort(function (first, second) {
      return categoryRank(first) - categoryRank(second) || first.localeCompare(second);
    }).map(function (item) {
      return '<option value="' + escapeHtml(item) + '">' + escapeHtml(categoryLabel(item)) + '</option>';
    }).join("");
    byId("area-filter").value = area;
    byId("group-filter").value = group;
    byId("event-filter").value = eventId;
    byId("category-filter").value = category;
    byId("sex-filter").value = sex;
    byId("group-filter").disabled = !area;
    byId("event-filter").disabled = !group;
    renderFilterTabs();
  }
  function renderMarks() {
    var marks = state.marks;
    var latestHead = document.querySelector("#latest-section thead tr");
    if (latestHead) latestHead.innerHTML = "<th>" + t("Atleta") + "</th><th>" + t("Prueba") + "</th><th>" + t("Categoria") + "</th><th>" + t("Marca") + "</th><th>" + t("Fecha") + "</th><th>" + t("Ciudad") + "</th>" + publicActionHeader();
    byId("results-title").textContent = t("Ultimas marcas registradas");
    byId("marks-body").innerHTML = marks.map(function (mark) {
      return '<tr><td><button class="athlete-button" type="button" data-athlete-id="' + mark.athleteId + '">' +
        escapeHtml(mark.athlete) + '</button></td><td>' + eventCell(mark) +
        '</td><td>' + escapeHtml(categoryLabel(mark.category)) + '</td><td>' + resultCell(mark, false) + '</td><td>' +
        dateCell(mark) + '</td><td>' + cityCell(mark) + '</td>' + publicActions(mark) + '</tr>';
    }).join("");
    byId("marks-empty").classList.toggle("hidden", marks.length > 0);
  }

  function rankingKey(grouped) { return String(grouped.event.id) + "|" + (grouped.category || "all"); }
  function historyKey(category, eventGroup) { return category + "|" + (eventGroup.event.eventId || [eventGroup.event.area, eventGroup.event.eventGroup, eventGroup.event.event].join("|")); }
  function areaRank(area) {
    var order = ["pista_cubierta", "aire_libre", "ruta"];
    var position = order.indexOf(area);
    return position === -1 ? 99 : position;
  }
  function compareRankingGroups(first, second) {
    var group = groupLabel(first.event.eventGroup).localeCompare(groupLabel(second.event.eventGroup));
    return group || compareEvents(first.event, second.event) ||
      categoryRank(first.category) - categoryRank(second.category) || String(first.category || "").localeCompare(String(second.category || ""));
  }
  function rankingTable(grouped, includeHeading, options) {
    var opts = options || {};
    var key = rankingKey(grouped);
    var visible = state.rankingVisible[key] || 20;
    var rows = grouped.marks.slice(0, visible).map(function (mark, index) {
      return '<tr><td>' + (index + 1) + '</td><td><button class="athlete-button" type="button" data-athlete-id="' +
        mark.athleteId + '">' + escapeHtml(mark.athlete) + '</button></td>' + (opts.showEvent === false ? "" : "<td>" + eventCell(mark) + "</td>") +
        (opts.showCategory === false ? "" : "<td>" + escapeHtml(categoryTabLabel(mark.category)) + "</td>") + '<td>' + resultCell(mark, true) +
        '</td><td>' + dateCell(mark) + '</td><td>' + cityCell(mark) + '</td>' + publicActions(mark) + '</tr>';
    }).join("");
    var heading = includeHeading && grouped.category ? '<p class="ranking-category">' + escapeHtml(categoryLabel(grouped.category)) + '</p>' : "";
    var more = visible < grouped.marks.length
      ? '<button class="ranking-more" type="button" data-more-event="' + key + '" aria-label="' + escapeHtml(t("Mostrar 20 resultados mas")) + '">+</button>'
      : "";
    return '<section class="ranking-result">' + heading + '<div class="table-wrap"><table><thead><tr><th>#</th><th>' + t("Atleta") +
      '</th>' + (opts.showEvent === false ? "" : "<th>" + t("Prueba") + "</th>") + (opts.showCategory === false ? "" : "<th>" + t("Categoria") + "</th>") + '<th>' + t("Marca") +
      '</th><th>' + t("Fecha") + '</th><th>' + t("Ciudad") + '</th>' + publicActionHeader() + '</tr></thead><tbody>' + rows + '</tbody></table></div>' + more + '</section>';
  }
  function renderRanking() {
    if (!state.ranking) { renderFilterTabs(); return; }
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    var groups = state.ranking.groups.slice().sort(compareRankingGroups);
    byId("ranking-empty").classList.toggle("hidden", groups.length > 0);
    renderFilterTabs();
    if (eventId) {
      byId("ranking-groups").innerHTML = '<article class="card ranking-group">' + groups.map(function (grouped) { return rankingTable(grouped, false, { showEvent: false, showCategory: !category }); }).join("") + '</article>';
      return;
    }
    var showCategory = !category;
    var cardKeys = unique(groups.map(function (grouped) { return group ? String(grouped.event.id) : grouped.event.eventGroup; }));
    if (!group) cardKeys.sort(function (first, second) { return groupLabel(first).localeCompare(groupLabel(second)); });
    byId("ranking-groups").innerHTML = cardKeys.map(function (cardKey) {
      var cardGroups = groups.filter(function (grouped) { return (group ? String(grouped.event.id) : grouped.event.eventGroup) === cardKey; });
      var cardTitle = group ? t(cardGroups[0].event.name) : groupLabel(cardKey);
      if (!group) {
        var eventIds = unique(cardGroups.map(function (grouped) { return String(grouped.event.id); }));
        var tone = normalized(cardKey).replace(/[^a-z0-9]+/g, "-");
        var eventCards = eventIds.map(function (currentId) {
          var eventGroups = cardGroups.filter(function (grouped) { return String(grouped.event.id) === currentId; });
          var currentEvent = eventGroups[0].event;
          var eventTitle = area ? t(currentEvent.name) : t(areaLabel(currentEvent.area)) + " / " + t(currentEvent.name);
          return '<article class="card ranking-event-card"><h4>' + escapeHtml(eventTitle) + '</h4>' + eventGroups.map(function (grouped) { return rankingTable(grouped, showCategory, { showEvent: false, showCategory: false }); }).join("") + '</article>';
        }).join("");
        return '<section class="ranking-cluster ranking-cluster--' + tone + '"><h3>' + escapeHtml(cardTitle) + '</h3><div class="ranking-event-cards">' + eventCards + '</div></section>';
      }
      var contents = cardGroups.map(function (grouped) { return rankingTable(grouped, showCategory, { showEvent: false, showCategory: false }); }).join("");
      return '<article class="card ranking-group"><h3>' + escapeHtml(cardTitle) + '</h3>' + contents + '</article>';
    }).join("");
  }

  function renderHistory() {
    var history = state.history;
    byId("latest-section").classList.toggle("hidden", !!history || !!state.ranking);
    document.querySelector(".filters").classList.toggle("hidden", !!history);
    document.querySelector(".filters").toggleAttribute("hidden", !!history);
    byId("filter-tabs").classList.toggle("hidden", !!history);
    byId("filter-tabs").toggleAttribute("hidden", !!history);
    byId("ranking-section").classList.toggle("hidden", !!history || !state.ranking);
    byId("history-section").classList.toggle("hidden", !history);
    if (!history) return;
    byId("history-athlete-name").textContent = history.athlete.name;
    var seenAreas = unique(history.marks.map(function (mark) { return mark.area; }).filter(Boolean));
    var areas = ["pista_cubierta", "aire_libre", "ruta"].filter(function (area) {
      return seenAreas.indexOf(area) !== -1;
    }).concat(seenAreas.filter(function (area) {
      return areaRank(area) === 99;
    }).sort(function (first, second) {
      return areaLabel(first).localeCompare(areaLabel(second));
    }));
    if (areas.indexOf(state.historyArea) === -1) state.historyArea = areas[0] || "";
    var tabs = areas.length ? '<div class="history-tabs" role="tablist" aria-label="' + escapeHtml(t("Ámbito")) + '">' + areas.map(function (area) {
      var active = area === state.historyArea;
      return '<button class="history-tab' + (active ? " active" : "") + '" type="button" role="tab" aria-selected="' + (active ? "true" : "false") +
        '" data-history-area="' + escapeHtml(area) + '">' + escapeHtml(t(areaLabel(area))) + '</button>';
    }).join("") + '</div>' : "";
    var grouped = Object.create(null);
    history.marks.filter(function (mark) { return !state.historyArea || mark.area === state.historyArea; }).forEach(function (mark) {
      if (!grouped[mark.category]) grouped[mark.category] = Object.create(null);
      var key = mark.eventId || [mark.area, mark.eventGroup, mark.event].join("|");
      if (!grouped[mark.category][key]) grouped[mark.category][key] = { event: mark, marks: [] };
      grouped[mark.category][key].marks.push(mark);
    });
    var categories = Object.keys(grouped).sort(function (first, second) {
      return categoryRank(first) - categoryRank(second) || first.localeCompare(second);
    });
    byId("history-categories").innerHTML = tabs + categories.map(function (category) {
      var events = Object.keys(grouped[category]).map(function (key) { return grouped[category][key]; }).sort(function (first, second) {
        var rank = eventRank(first.event.eventId) - eventRank(second.event.eventId);
        var group = groupLabel(first.event.eventGroup).localeCompare(groupLabel(second.event.eventGroup));
        return rank || group || compareEvents(first.event, second.event);
      });
      return "<section class=\"history-category-group\"><div class=\"category-pill category-pill--" + escapeHtml(normalized(categoryBase(category)).replace(/[^a-z0-9]+/g, "-")) + "\">" +
        escapeHtml(categoryTabLabel(category)) + "</div><article class=\"card category-history\">" + events.map(function (eventGroup) {
          var key = historyKey(category, eventGroup);
          var sortedMarks = eventGroup.marks.slice().sort(compareHistory);
          var visible = state.historyVisible[key] || 1;
          var rows = sortedMarks.slice(0, visible).map(function (mark, index) {
            var personalBest = index === 0 ? ' <span class="personal-best-badge">' + escapeHtml(t("Mejor marca personal")) + '</span>' : "";
            return "<tr><td>" + resultCell(mark, true) + personalBest + "</td><td>" + dateCell(mark) +
              "</td><td>" + cityCell(mark) + "</td>" + publicActions(mark) + "</tr>";
          }).join("");
          var less = visible > 1
            ? '<button class="history-toggle" type="button" data-less-history="' + escapeHtml(key) + '" aria-label="' + escapeHtml(t("Mostrar menos resultados")) + '">-</button>'
            : "";
          var more = visible < sortedMarks.length
            ? '<button class="history-toggle" type="button" data-more-history="' + escapeHtml(key) + '" aria-label="' + escapeHtml(t("Mostrar 20 resultados mas")) + '">+</button>'
            : "";
          var controls = more || less ? '<div class="history-controls">' + less + more + '</div>' : "";
          return "<section class=\"event-history\"><h4>" + escapeHtml(eventShortLabel(eventGroup.event)) + "</h4><div class=\"table-wrap\"><table><thead><tr><th>" +
            t("Marca") + "</th><th>" + t("Fecha") + "</th><th>" + t("Ciudad") + "</th>" + publicActionHeader() + "</tr></thead><tbody>" + rows + "</tbody></table></div>" + controls + "</section>";
        }).join("") + "</article></section>";
    }).join("");
    byId("history-empty").classList.toggle("hidden", history.marks.length > 0);
  }

  function render() {
    renderFilters();
    renderSearch();
    renderMarks();
    renderRanking();
    renderHistory();
    byId("count-marks").textContent = state.counts.marks || 0;
    byId("count-events").textContent = state.counts.events || 0;
    byId("count-athletes").textContent = state.counts.athletes || 0;
  }
  async function loadMarks() {
    try {
      var response = await fetch("/api/public/marks");
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      var authResponse = await fetch("/api/auth/status");
      var auth = await authResponse.json().catch(function () { return {}; });
      var adminData = { athletes: [], cities: [] };
      if (auth.user) {
        var adminResponse = await fetch("/api/bootstrap");
        adminData = await adminResponse.json().catch(function () { return { athletes: [], cities: [] }; });
        if (!adminResponse.ok) adminData = { athletes: [], cities: [] };
      }
      if (window.RankingI18n.setManagedTranslations) window.RankingI18n.setManagedTranslations(data.translations || []);
      state = {
        events: data.events || [],
        categories: data.categories || [],
        athletes: data.athletes || [],
        cities: adminData.cities || [],
        marks: data.marks || [],
        counts: data.counts || {},
        ranking: null,
        rankingVisible: {},
        history: null,
        historyVisible: {},
        historyArea: "",
        currentUser: auth.user || null,
        editableAthleteIds: (adminData.athletes || []).filter(function (athlete) { return athlete.canEditMarks; }).map(function (athlete) { return String(athlete.id); }),
        editingMarkId: null
      };
      showError("");
      render();
      restoreFromUrl();
    } catch (error) {
      showError(error.message);
    }
  }

  async function loadRanking(updateUrl) {
    if (updateUrl !== false) updateUrl = true;
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    state.history = null;
    byId("athlete-search").value = "";
    renderHistory();
    var category = byId("category-filter").value;
    var sex = byId("sex-filter").value;
    if (!area && !group && !eventId && !category && !sex) {
      state.ranking = null;
      state.rankingVisible = {};
      renderHistory();
      if (updateUrl) clearUrl(false);
      return;
    }
    try {
      var url = "/api/public/ranking?area=" + encodeURIComponent(area) + "&eventGroup=" + encodeURIComponent(group) + "&eventId=" + encodeURIComponent(eventId) + "&category=" + encodeURIComponent(category) + "&sex=" + encodeURIComponent(sex);
      var response = await fetch(url);
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state.ranking = data;
      state.rankingVisible = {};
      showError("");
      renderRanking();
      renderHistory();
      if (updateUrl) setUrl(currentFilterParams(), false);
    } catch (error) {
      showError(error.message);
    }
  }

  async function loadHistory(athleteId, updateUrl) {
    if (updateUrl !== false) updateUrl = true;
    try {
      var response = await fetch("/api/public/athletes/" + encodeURIComponent(athleteId) + "/history");
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state.history = data;
      state.historyVisible = {};
      state.historyArea = "";
      byId("athlete-search").value = data.athlete.name;
      showError("");
      renderHistory();
      if (updateUrl) setUrl({ athlete: athleteId }, false);
    } catch (error) {
      showError(error.message);
    }
  }
  function restoreFromUrl() {
    var params = new URLSearchParams(window.location.search);
    var athlete = params.get("athlete");
    if (athlete) {
      loadHistory(athlete, false);
      return;
    }
    var eventId = params.get("event") || "";
    var event = eventId ? state.events.find(function (item) { return String(item.id) === eventId; }) : null;
    byId("area-filter").value = params.get("area") || (event ? event.area : "");
    renderFilters();
    byId("group-filter").value = params.get("group") || (event ? event.eventGroup : "");
    renderFilters();
    byId("event-filter").value = eventId;
    byId("category-filter").value = params.get("category") || "";
    byId("sex-filter").value = params.get("sex") || "";
    renderFilters();
    if (byId("area-filter").value || byId("group-filter").value || byId("event-filter").value || byId("category-filter").value || byId("sex-filter").value) {
      loadRanking(false).then(function () {
        if (byId("sex-filter").value && !byId("event-filter").value) {
          selectFirstAvailablePath();
          loadRanking(false);
        }
      });
    }
  }
  function selectFirstAvailablePath() {
    var areas = tabAreas();
    var area = areas[0] || "";
    var groups = area ? tabGroups(area) : [];
    var group = groups[0] || "";
    var events = area && group ? tabEvents(area, group) : [];
    byId("area-filter").value = area;
    renderFilters();
    byId("group-filter").value = group;
    renderFilters();
    byId("event-filter").value = events.length ? String(events[0].id) : "";
    renderFilters();
  }

  function selectSearchedAthlete() {
    var query = normalized(byId("athlete-search").value);
    var matches = state.athletes.filter(function (athlete) {
      return normalized(athlete.name) === query || normalized(athlete.name).includes(query);
    });
    if (query && matches.length === 1) {
      loadHistory(matches[0].id);
    } else {
      showError("Selecciona un atleta de la lista.");
    }
  }


  function findPublicMark(id) {
    var target = String(id);
    var sources = [state.marks || []];
    if (state.ranking && state.ranking.groups) state.ranking.groups.forEach(function (grouped) { sources.push(grouped.marks || []); });
    if (state.history && state.history.marks) sources.push(state.history.marks || []);
    for (var sourceIndex = 0; sourceIndex < sources.length; sourceIndex += 1) {
      for (var index = 0; index < sources[sourceIndex].length; index += 1) {
        if (String(sources[sourceIndex][index].id) === target) return sources[sourceIndex][index];
      }
    }
    return null;
  }
  async function requestPublicAdmin(path, options) {
    var response = await fetch("/api" + path, Object.assign({ headers: { "Content-Type": "application/json" } }, options || {}));
    var data = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(data.error || "No se ha podido guardar el cambio.");
    return data;
  }
  function publicMarkPayload(mark, result, date, cityId, technicalInfo, trackName) {
    return {
      athleteId: mark.athleteId,
      eventId: mark.eventId,
      cityId: cityId || mark.cityId,
      result: result,
      date: date,
      trackName: trackName == null ? (mark.trackName || "") : trackName,
      technicalInfo: technicalInfo == null ? (mark.technicalInfo || "") : technicalInfo
    };
  }
  function renderCurrentPublicViews() {
    renderMarks();
    renderRanking();
    renderHistory();
  }
  function editPublicMark(id) {
    state.editingMarkId = String(id);
    renderCurrentPublicViews();
  }
  function cancelPublicEdit() {
    state.editingMarkId = null;
    renderCurrentPublicViews();
  }
  async function savePublicMark(id, row) {
    var mark = findPublicMark(id);
    if (!mark || !row) return;
    var resultInput = row.querySelector("[data-public-edit-result]");
    var technicalInput = row.querySelector("[data-public-edit-technical]");
    var dateInput = row.querySelector("[data-public-edit-date]");
    var cityInput = row.querySelector("[data-public-edit-city]");
    var trackInput = row.querySelector("[data-public-edit-track]");
    var cityId = cityInput ? publicCityId(mark, cityInput.value) : mark.cityId;
    if (cityInput && !cityId) { showError("Selecciona una ciudad existente."); return; }
    try {
      await requestPublicAdmin("/marks/" + encodeURIComponent(id), { method: "PUT", body: JSON.stringify(publicMarkPayload(mark, normalizeResultText(resultInput ? resultInput.value : mark.result), dateInput ? dateInput.value : mark.date, cityId, technicalInput ? technicalInput.value : mark.technicalInfo, trackInput ? trackInput.value : mark.trackName)) });
      state.editingMarkId = null;
      await loadMarks();
    } catch (error) {
      showError(error.message);
    }
  }
  async function deletePublicMark(id) {
    if (!window.confirm(t("Eliminar") + "?")) return;
    try {
      await requestPublicAdmin("/marks/" + encodeURIComponent(id), { method: "DELETE" });
      await loadMarks();
    } catch (error) {
      showError(error.message);
    }
  }


  byId("filter-tabs").addEventListener("click", async function (event) {
    var tab = event.target.closest("[data-filter-level]");
    if (!tab) return;
    if (tab.dataset.filterLevel === "sex") {
      byId("sex-filter").value = tab.dataset.filterValue;
      byId("category-filter").value = "";
      byId("area-filter").value = "";
      byId("group-filter").value = "";
      byId("event-filter").value = "";
      renderFilters();
      await loadRanking(false);
      selectFirstAvailablePath();
      loadRanking();
      return;
    }
    if (tab.dataset.filterLevel === "category") {
      byId("category-filter").value = tab.dataset.filterValue;
      byId("area-filter").value = "";
      byId("group-filter").value = "";
      byId("event-filter").value = "";
      renderFilters();
      await loadRanking(false);
      selectFirstAvailablePath();
      loadRanking();
      return;
    }
    if (tab.dataset.filterLevel === "area") {
      var firstGroup = firstTabGroupForArea(tab.dataset.filterValue);
      byId("area-filter").value = tab.dataset.filterValue;
      renderFilters();
      byId("group-filter").value = firstGroup;
      renderFilters();
      byId("event-filter").value = firstTabEventForGroup(tab.dataset.filterValue, firstGroup);
      renderFilters();
      loadRanking();
      return;
    }
    if (tab.dataset.filterLevel === "group") {
      byId("group-filter").value = tab.dataset.filterValue;
      renderFilters();
      byId("event-filter").value = firstTabEventForGroup(byId("area-filter").value, tab.dataset.filterValue);
      renderFilters();
      loadRanking();
      return;
    }
    if (tab.dataset.filterLevel === "event") byId("event-filter").value = tab.dataset.filterValue;
    renderFilters();
    loadRanking();
  });
  byId("athlete-search-form").addEventListener("submit", function (event) {
    event.preventDefault();
    selectSearchedAthlete();
  });
  byId("athlete-search").addEventListener("change", selectSearchedAthlete);
  byId("area-filter").addEventListener("change", function () { var area = byId("area-filter").value, group = firstGroupForArea(area); renderFilters(); byId("group-filter").value = group; renderFilters(); byId("event-filter").value = firstEventForGroup(area, group); renderFilters(); loadRanking(); });
  byId("group-filter").addEventListener("change", function () { var area = byId("area-filter").value, group = byId("group-filter").value; renderFilters(); byId("event-filter").value = firstEventForGroup(area, group); renderFilters(); loadRanking(); });
  byId("event-filter").addEventListener("change", loadRanking);
  byId("category-filter").addEventListener("change", loadRanking);
  byId("clear-filters").addEventListener("click", function () {
    byId("area-filter").value = "";
    byId("group-filter").value = "";
    byId("event-filter").value = "";
    byId("category-filter").value = "";
    byId("sex-filter").value = "";
    byId("athlete-search").value = "";
    state.ranking = null;
    state.rankingVisible = {};
    state.history = null;
    state.historyVisible = {};
    renderFilters();
    renderMarks();
    renderHistory();
    clearUrl(false);
  });
  byId("marks-body").addEventListener("click", function (event) {
    var save = event.target.closest("[data-public-save-mark]");
    if (save) { savePublicMark(save.dataset.publicSaveMark, save.closest("tr")); return; }
    var cancel = event.target.closest("[data-public-cancel-edit]");
    if (cancel) { cancelPublicEdit(); return; }
    var edit = event.target.closest("[data-public-edit-mark]");
    if (edit) { editPublicMark(edit.dataset.publicEditMark); return; }
    var remove = event.target.closest("[data-public-delete-mark]");
    if (remove) { deletePublicMark(remove.dataset.publicDeleteMark); return; }
    var button = event.target.closest("[data-athlete-id]");
    if (button) loadHistory(button.dataset.athleteId);
  });
  byId("ranking-groups").addEventListener("click", function (event) {
    var save = event.target.closest("[data-public-save-mark]");
    if (save) { savePublicMark(save.dataset.publicSaveMark, save.closest("tr")); return; }
    var cancel = event.target.closest("[data-public-cancel-edit]");
    if (cancel) { cancelPublicEdit(); return; }
    var edit = event.target.closest("[data-public-edit-mark]");
    if (edit) { editPublicMark(edit.dataset.publicEditMark); return; }
    var remove = event.target.closest("[data-public-delete-mark]");
    if (remove) { deletePublicMark(remove.dataset.publicDeleteMark); return; }
    var athlete = event.target.closest("[data-athlete-id]");
    if (athlete) {
      loadHistory(athlete.dataset.athleteId);
      return;
    }
    var more = event.target.closest("[data-more-event]");
    if (more) {
      var key = more.dataset.moreEvent;
      state.rankingVisible[key] = (state.rankingVisible[key] || 20) + 20;
      renderRanking();
    }
  });
  byId("history-back").addEventListener("click", function () {
    state.history = null;
    state.historyVisible = {};
    byId("athlete-search").value = "";
    showError("");
    renderHistory();
    if (state.ranking) setUrl(currentFilterParams(), false);
    else clearUrl(false);
  });
  window.addEventListener("popstate", function () {
    state.history = null;
    state.historyVisible = {};
    state.ranking = null;
    state.rankingVisible = {};
    byId("athlete-search").value = "";
    byId("area-filter").value = "";
    byId("group-filter").value = "";
    byId("event-filter").value = "";
    byId("category-filter").value = "";
    byId("sex-filter").value = "";
    render();
    restoreFromUrl();
  });
  document.addEventListener("rankinglanguagechange", function () {
    renderSearch();
    renderFilters();
    renderMarks();
    renderRanking();
    renderHistory();
  });
  byId("history-categories").addEventListener("click", function (event) {
    var save = event.target.closest("[data-public-save-mark]");
    if (save) { savePublicMark(save.dataset.publicSaveMark, save.closest("tr")); return; }
    var cancel = event.target.closest("[data-public-cancel-edit]");
    if (cancel) { cancelPublicEdit(); return; }
    var edit = event.target.closest("[data-public-edit-mark]");
    if (edit) { editPublicMark(edit.dataset.publicEditMark); return; }
    var remove = event.target.closest("[data-public-delete-mark]");
    if (remove) { deletePublicMark(remove.dataset.publicDeleteMark); return; }
    var more = event.target.closest("[data-more-history]");
    if (more) {
      var key = more.dataset.moreHistory;
      state.historyVisible[key] = (state.historyVisible[key] || 1) + 20;
      renderHistory();
    }
    var tab = event.target.closest("[data-history-area]");
    if (tab) {
      state.historyArea = tab.dataset.historyArea;
      renderHistory();
    }
    var less = event.target.closest("[data-less-history]");
    if (less) {
      state.historyVisible[less.dataset.lessHistory] = 1;
      renderHistory();
    }
  });

  loadMarks();
}());
