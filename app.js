(function () {
  "use strict";

  var state = { events: [], categories: [], athletes: [], marks: [], counts: {}, ranking: null, history: null };

  function byId(id) { return document.getElementById(id); }
  function t(value) { return window.RankingI18n.t(value); }
  function eventLabel(event) { return t(event.name); }
  function normalized(value) { return String(value || "").trim().toLocaleLowerCase("es"); }
  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }
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
    var order = ["sub8", "sub10", "sub12", "sub14", "sub16", "sub18", "sub20", "sub23", "senior", "master"];
    var position = order.indexOf(category);
    return position === -1 ? order.length : position;
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
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    byId("event-filter").innerHTML = "<option value=\"\">" + t("Todas las pruebas") + "</option>" +
      state.events.map(function (event) {
        return "<option value=\"" + event.id + "\">" + escapeHtml(eventLabel(event)) + "</option>";
      }).join("");
    byId("category-filter").innerHTML = "<option value=\"\">" + t("Todas las categorias") + "</option>" +
      state.categories.map(function (item) {
        return "<option value=\"" + item + "\">" + escapeHtml(item.toUpperCase()) + "</option>";
      }).join("");
    byId("event-filter").value = eventId;
    byId("category-filter").value = category;
    byId("category-filter").disabled = !eventId;
  }
  function renderMarks() {
    var marks = state.ranking ? state.ranking.marks : state.marks;
    byId("results-title").textContent = state.ranking ? t("Ranking por prueba") : t("Ultimas marcas registradas");
    byId("marks-body").innerHTML = marks.map(function (mark) {
      return "<tr><td><button class=\"athlete-button\" type=\"button\" data-athlete-id=\"" + mark.athleteId + "\">" +
        escapeHtml(mark.athlete) + "</button></td><td>" + escapeHtml(t(mark.event)) +
        "</td><td>" + escapeHtml(mark.category) + "</td><td>" + escapeHtml(mark.result) + "</td><td>" +
        formatDate(mark.date) + "</td><td>" + escapeHtml(mark.track) + "</td></tr>";
    }).join("");
    byId("marks-empty").classList.toggle("hidden", marks.length > 0);
  }

  function renderHistory() {
    var history = state.history;
    byId("latest-section").classList.toggle("hidden", !!history);
    byId("history-section").classList.toggle("hidden", !history);
    if (!history) return;
    byId("history-athlete-name").textContent = history.athlete.name;
    var grouped = Object.create(null);
    history.marks.forEach(function (mark) {
      if (!grouped[mark.category]) grouped[mark.category] = Object.create(null);
      if (!grouped[mark.category][mark.event]) grouped[mark.category][mark.event] = [];
      grouped[mark.category][mark.event].push(mark);
    });
    var categories = Object.keys(grouped).sort(function (first, second) {
      return categoryRank(first) - categoryRank(second) || first.localeCompare(second);
    });
    byId("history-categories").innerHTML = categories.map(function (category) {
      var events = Object.keys(grouped[category]).sort(function (first, second) {
        return t(first).localeCompare(t(second));
      });
      return "<article class=\"card category-history\"><p class=\"eyebrow\">" + t("Categoria") + "</p><h3>" +
        escapeHtml(category.toUpperCase()) + "</h3>" + events.map(function (event) {
          var rows = grouped[category][event].slice().sort(compareHistory).map(function (mark) {
            return "<tr><td><strong>" + escapeHtml(mark.result) + "</strong></td><td>" + formatDate(mark.date) +
              "</td><td>" + escapeHtml(mark.track) + "</td></tr>";
          }).join("");
          return "<section class=\"event-history\"><h4>" + escapeHtml(t(event)) + "</h4><div class=\"table-wrap\"><table><thead><tr><th>" +
            t("Marca") + "</th><th>" + t("Fecha") + "</th><th>" + t("Sitio") + "</th></tr></thead><tbody>" + rows + "</tbody></table></div></section>";
        }).join("") + "</article>";
    }).join("");
    byId("history-empty").classList.toggle("hidden", history.marks.length > 0);
  }

  function render() {
    renderFilters();
    renderSearch();
    renderMarks();
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
      state = {
        events: data.events || [],
        categories: data.categories || [],
        athletes: data.athletes || [],
        marks: data.marks || [],
        counts: data.counts || {},
        ranking: null,
        history: null
      };
      showError("");
      render();
    } catch (error) {
      showError(error.message);
    }
  }

  async function loadRanking() {
    var eventId = byId("event-filter").value;
    state.history = null;
    byId("athlete-search").value = "";
    renderHistory();
    if (!eventId) {
      state.ranking = null;
      byId("category-filter").disabled = true;
      renderMarks();
      return;
    }
    byId("category-filter").disabled = false;
    var category = byId("category-filter").value;
    try {
      var url = "/api/public/ranking?eventId=" + encodeURIComponent(eventId) + "&category=" + encodeURIComponent(category);
      var response = await fetch(url);
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state.ranking = data;
      showError("");
      renderMarks();
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
  byId("event-filter").addEventListener("change", function () {
    if (!byId("event-filter").value) byId("category-filter").value = "";
    loadRanking();
  });
  byId("category-filter").addEventListener("change", loadRanking);
  byId("clear-filters").addEventListener("click", function () {
    byId("event-filter").value = "";
    byId("category-filter").value = "";
    byId("athlete-search").value = "";
    state.ranking = null;
    state.history = null;
    renderFilters();
    renderMarks();
    renderHistory();
  });
  byId("marks-body").addEventListener("click", function (event) {
    var button = event.target.closest("[data-athlete-id]");
    if (button) loadHistory(button.dataset.athleteId);
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
    renderHistory();
  });

  loadMarks();
}());
