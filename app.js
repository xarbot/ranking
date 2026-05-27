(function () {
  "use strict";

  var state = { events: [], categories: [], athletes: [], marks: [], counts: {}, ranking: null, rankingVisible: {}, history: null };

  function byId(id) { return document.getElementById(id); }
  function t(value) { return window.RankingI18n.t(value); }
  function areaLabel(area) { return { pista_cubierta: "Pista Cubierta", aire_libre: "Aire Libre", ruta: "Ruta" }[area] || area; }
  function groupLabel(group) { return t(normalized(group).replace("ç", "c") === "llancaments" ? "Llançaments" : group); }
  function eventLabel(event) { return t(areaLabel(event.area)) + " / " + groupLabel(event.eventGroup) + " / " + t(event.name || event.event || ""); }
  function categoryLabel(value) { var parts = String(value || "").split(" - "); parts[0] = parts[0].replace(/^Master /, t("Master") + " "); if (parts[0] === "Senior") parts[0] = t("Senior"); if (parts[1]) parts[1] = t(parts[1]); return parts.join(" - "); }
  function normalized(value) { return String(value || "").trim().toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, ""); }
  function raceDistance(event) { var name = normalized(event.name || event.event).replace(",", "."), match; if (normalized(event.eventGroup) !== "curses") return null; if (name === "milla") return 1609; if (name.indexOf("mitja") === 0) return 21097; if (name === "marato") return 42195; match = /^(\d+(?:\.\d+)?)\s*(km)?/.exec(name); return match ? Number(match[1]) * (match[2] ? 1000 : 1) : null; }
  function compareEvents(first, second) { var firstDistance = raceDistance(first), secondDistance = raceDistance(second); if (firstDistance !== null && secondDistance !== null) return firstDistance - secondDistance; return t(first.name || first.event).localeCompare(t(second.name || second.event)); }
  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }
  function detailLine(value) { return value ? '<span class="cell-detail">' + escapeHtml(value) + '</span>' : ""; }
  function technicalDetail(value) { return normalized(value) === "no" ? "" : detailLine(value); }
  function eventCell(mark) { return escapeHtml(eventLabel(mark)) + technicalDetail(mark.technicalInfo); }
  function cityCell(mark) { return escapeHtml(mark.city) + detailLine(mark.trackName); }
  function unique(values) { return values.filter(function (value, index) { return values.indexOf(value) === index; }); }
  function formatDate(value) {
    if (!value) return "";
    var locale = window.RankingI18n.language() === "ca" ? "ca-ES" : "es-ES";
    return new Date(value + "T00:00:00").toLocaleDateString(locale);
  }
  function resultValue(result) {
    var clean = String(result || "").replace(",", ".").replace(/[^\d:.]/g, "");
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

  function renderSearch() {
    byId("public-athletes").innerHTML = state.athletes.map(function (athlete) {
      return "<option value=\"" + escapeHtml(athlete.name) + "\"></option>";
    }).join("");
  }
  function renderFilters() {
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    var areas = unique(state.events.map(function (event) { return event.area; }));
    if (!areas.includes(area)) area = "";
    var groups = area ? unique(state.events.filter(function (event) { return event.area === area; }).map(function (event) { return event.eventGroup; })) : [];
    if (!groups.includes(group)) group = "";
    var events = group ? state.events.filter(function (event) { return event.area === area && event.eventGroup === group; }).sort(compareEvents) : [];
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
    byId("group-filter").disabled = !area;
    byId("event-filter").disabled = !group;
  }
  function renderMarks() {
    var marks = state.marks;
    byId("results-title").textContent = t("Ultimas marcas registradas");
    byId("marks-body").innerHTML = marks.map(function (mark) {
      return '<tr><td><button class="athlete-button" type="button" data-athlete-id="' + mark.athleteId + '">' +
        escapeHtml(mark.athlete) + '</button></td><td>' + eventCell(mark) +
        '</td><td>' + escapeHtml(categoryLabel(mark.category)) + '</td><td>' + escapeHtml(mark.result) + '</td><td>' +
        formatDate(mark.date) + '</td><td>' + cityCell(mark) + '</td></tr>';
    }).join("");
    byId("marks-empty").classList.toggle("hidden", marks.length > 0);
  }

  function rankingKey(grouped) { return String(grouped.event.id) + "|" + (grouped.category || "all"); }
  function compareRankingGroups(first, second) {
    var group = groupLabel(first.event.eventGroup).localeCompare(groupLabel(second.event.eventGroup));
    return group || compareEvents(first.event, second.event) ||
      categoryRank(first.category) - categoryRank(second.category) || String(first.category || "").localeCompare(String(second.category || ""));
  }
  function rankingTable(grouped, includeHeading) {
    var key = rankingKey(grouped);
    var visible = state.rankingVisible[key] || 20;
    var rows = grouped.marks.slice(0, visible).map(function (mark, index) {
      return '<tr><td>' + (index + 1) + '</td><td><button class="athlete-button" type="button" data-athlete-id="' +
        mark.athleteId + '">' + escapeHtml(mark.athlete) + '</button></td><td>' + eventCell(mark) +
        '</td><td>' + escapeHtml(categoryLabel(mark.category)) + '</td><td><strong>' + escapeHtml(mark.result) +
        '</strong></td><td>' + formatDate(mark.date) + '</td><td>' + cityCell(mark) + '</td></tr>';
    }).join("");
    var heading = includeHeading && grouped.category ? '<p class="ranking-category">' + escapeHtml(categoryLabel(grouped.category)) + '</p>' : "";
    var more = visible < grouped.marks.length
      ? '<button class="ranking-more" type="button" data-more-event="' + key + '" aria-label="' + escapeHtml(t("Mostrar 20 resultados mas")) + '">+</button>'
      : "";
    return '<section class="ranking-result">' + heading + '<div class="table-wrap"><table><thead><tr><th>#</th><th>' + t("Atleta") +
      '</th><th>' + t("Prueba") + '</th><th>' + t("Categoria") + '</th><th>' + t("Marca") +
      '</th><th>' + t("Fecha") + '</th><th>' + t("Ciudad") + '</th></tr></thead><tbody>' + rows + '</tbody></table></div>' + more + '</section>';
  }
  function renderRanking() {
    if (!state.ranking) return;
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    var event = state.events.find(function (item) { return String(item.id) === eventId; });
    var selected = event ? eventLabel(event) : [area ? t(areaLabel(area)) : "", group ? groupLabel(group) : ""].filter(Boolean).join(" / ");
    var title = selected || categoryLabel(category);
    if (selected && category) title += " - " + categoryLabel(category);
    byId("ranking-title").textContent = title;
    var groups = state.ranking.groups.slice().sort(compareRankingGroups);
    byId("ranking-empty").classList.toggle("hidden", groups.length > 0);
    if (eventId) {
      byId("ranking-groups").innerHTML = '<article class="card ranking-group">' + groups.map(function (grouped) { return rankingTable(grouped, false); }).join("") + '</article>';
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
          return '<article class="card ranking-event-card"><h4>' + escapeHtml(eventTitle) + '</h4>' + eventGroups.map(function (grouped) { return rankingTable(grouped, showCategory); }).join("") + '</article>';
        }).join("");
        return '<section class="ranking-cluster ranking-cluster--' + tone + '"><h3>' + escapeHtml(cardTitle) + '</h3><div class="ranking-event-cards">' + eventCards + '</div></section>';
      }
      var contents = cardGroups.map(function (grouped) { return rankingTable(grouped, showCategory); }).join("");
      return '<article class="card ranking-group"><h3>' + escapeHtml(cardTitle) + '</h3>' + contents + '</article>';
    }).join("");
  }

  function renderHistory() {
    var history = state.history;
    byId("latest-section").classList.toggle("hidden", !!history || !!state.ranking);
    byId("ranking-section").classList.toggle("hidden", !!history || !state.ranking);
    byId("history-section").classList.toggle("hidden", !history);
    if (!history) return;
    byId("history-athlete-name").textContent = history.athlete.name;
    var grouped = Object.create(null);
    history.marks.forEach(function (mark) {
      if (!grouped[mark.category]) grouped[mark.category] = Object.create(null);
      var event = eventLabel(mark);
      if (!grouped[mark.category][event]) grouped[mark.category][event] = [];
      grouped[mark.category][event].push(mark);
    });
    var categories = Object.keys(grouped).sort(function (first, second) {
      return categoryRank(first) - categoryRank(second) || first.localeCompare(second);
    });
    byId("history-categories").innerHTML = categories.map(function (category) {
      var events = Object.keys(grouped[category]).sort(function (first, second) {
        return t(first).localeCompare(t(second));
      });
      return "<article class=\"card category-history\"><p class=\"eyebrow\">" + t("Categoria") + "</p><h3>" +
        escapeHtml(categoryLabel(category)) + "</h3>" + events.map(function (event) {
          var rows = grouped[category][event].slice().sort(compareHistory).map(function (mark) {
            return "<tr><td><strong>" + escapeHtml(mark.result) + "</strong></td><td>" + formatDate(mark.date) +
              "</td><td>" + cityCell(mark) + "</td></tr>";
          }).join("");
          return "<section class=\"event-history\"><h4>" + escapeHtml(event) + "</h4><div class=\"table-wrap\"><table><thead><tr><th>" +
            t("Marca") + "</th><th>" + t("Fecha") + "</th><th>" + t("Ciudad") + "</th></tr></thead><tbody>" + rows + "</tbody></table></div></section>";
        }).join("") + "</article>";
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
      if (window.RankingI18n.setManagedTranslations) window.RankingI18n.setManagedTranslations(data.translations || []);
      state = {
        events: data.events || [],
        categories: data.categories || [],
        athletes: data.athletes || [],
        marks: data.marks || [],
        counts: data.counts || {},
        ranking: null,
        rankingVisible: {},
        history: null
      };
      showError("");
      render();
    } catch (error) {
      showError(error.message);
    }
  }

  async function loadRanking() {
    var area = byId("area-filter").value;
    var group = byId("group-filter").value;
    var eventId = byId("event-filter").value;
    state.history = null;
    byId("athlete-search").value = "";
    renderHistory();
    var category = byId("category-filter").value;
    if (!area && !group && !eventId && !category) {
      state.ranking = null;
      state.rankingVisible = {};
      renderHistory();
      return;
    }
    try {
      var url = "/api/public/ranking?area=" + encodeURIComponent(area) + "&eventGroup=" + encodeURIComponent(group) + "&eventId=" + encodeURIComponent(eventId) + "&category=" + encodeURIComponent(category);
      var response = await fetch(url);
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state.ranking = data;
      state.rankingVisible = {};
      showError("");
      renderRanking();
      renderHistory();
    } catch (error) {
      showError(error.message);
    }
  }

  async function loadHistory(athleteId) {
    try {
      var response = await fetch("/api/public/athletes/" + encodeURIComponent(athleteId) + "/history");
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state.history = data;
      byId("athlete-search").value = data.athlete.name;
      showError("");
      renderHistory();
    } catch (error) {
      showError(error.message);
    }
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

  byId("athlete-search-form").addEventListener("submit", function (event) {
    event.preventDefault();
    selectSearchedAthlete();
  });
  byId("athlete-search").addEventListener("change", selectSearchedAthlete);
  byId("area-filter").addEventListener("change", function () { byId("group-filter").value = ""; byId("event-filter").value = ""; renderFilters(); loadRanking(); });
  byId("group-filter").addEventListener("change", function () { byId("event-filter").value = ""; renderFilters(); loadRanking(); });
  byId("event-filter").addEventListener("change", loadRanking);
  byId("category-filter").addEventListener("change", loadRanking);
  byId("clear-filters").addEventListener("click", function () {
    byId("area-filter").value = "";
    byId("group-filter").value = "";
    byId("event-filter").value = "";
    byId("category-filter").value = "";
    byId("athlete-search").value = "";
    state.ranking = null;
    state.rankingVisible = {};
    state.history = null;
    renderFilters();
    renderMarks();
    renderHistory();
  });
  byId("marks-body").addEventListener("click", function (event) {
    var button = event.target.closest("[data-athlete-id]");
    if (button) loadHistory(button.dataset.athleteId);
  });
  byId("ranking-groups").addEventListener("click", function (event) {
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
    byId("athlete-search").value = "";
    showError("");
    renderHistory();
  });
  document.addEventListener("rankinglanguagechange", function () {
    renderSearch();
    renderFilters();
    renderMarks();
    renderRanking();
    renderHistory();
  });

  loadMarks();
}());
