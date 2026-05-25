(function () {
  "use strict";

  var state = { events: [], categories: [], marks: [] };

  function byId(id) { return document.getElementById(id); }
  function t(value) { return window.RankingI18n.t(value); }
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
  function showError(message) {
    byId("public-error").textContent = t(message || "");
    byId("public-error").classList.toggle("hidden", !message);
  }

  function renderFilters() {
    var selectedEvent = byId("event-filter").value;
    var selectedCategory = byId("category-filter").value;
    byId("event-filter").innerHTML = "<option value=\"\">" + t("Todas las pruebas") + "</option>" +
      state.events.map(function (event) {
        return "<option value=\"" + event.id + "\">" + escapeHtml(event.name) + "</option>";
      }).join("");
    byId("category-filter").innerHTML = "<option value=\"\">" + t("Todas las categorias") + "</option>" +
      state.categories.map(function (category) {
        return "<option value=\"" + category + "\">" + escapeHtml(category) + "</option>";
      }).join("");
    byId("event-filter").value = selectedEvent;
    byId("category-filter").value = selectedCategory;
  }
  function filteredMarks() {
    var eventId = byId("event-filter").value;
    var category = byId("category-filter").value;
    var athlete = normalized(byId("athlete-filter").value);
    return state.marks.filter(function (mark) {
      return (!eventId || String(mark.eventId) === eventId) &&
        (!category || mark.category === category) &&
        (!athlete || normalized(mark.athlete).includes(athlete));
    });
  }
  function renderMarks() {
    var marks = filteredMarks();
    byId("marks-body").innerHTML = marks.map(function (mark) {
      return "<tr><td><strong>" + escapeHtml(mark.athlete) + "</strong></td><td>" + escapeHtml(mark.event) +
        "</td><td>" + escapeHtml(mark.category) + "</td><td>" + escapeHtml(mark.result) + "</td><td>" +
        formatDate(mark.date) + "</td><td>" + escapeHtml(mark.track) + "</td></tr>";
    }).join("");
    byId("marks-empty").classList.toggle("hidden", marks.length > 0);
  }

  function render() {
    renderFilters();
    renderMarks();
    byId("count-marks").textContent = state.marks.length;
    byId("count-events").textContent = state.events.length;
    var athletes = new Set(state.marks.map(function (mark) { return mark.athlete; }));
    byId("count-athletes").textContent = athletes.size;
  }
  async function loadMarks() {
    try {
      var response = await fetch("/api/public/marks");
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(data.error || "No se puede conectar con el servidor de datos.");
      state = data;
      showError("");
      render();
    } catch (error) {
      showError(error.message);
    }
  }

  ["event-filter", "category-filter", "athlete-filter"].forEach(function (id) {
    byId(id).addEventListener("input", renderMarks);
  });
  byId("clear-filters").addEventListener("click", function () {
    byId("event-filter").value = "";
    byId("category-filter").value = "";
    byId("athlete-filter").value = "";
    renderMarks();
  });
  document.addEventListener("rankinglanguagechange", function () {
    renderFilters();
    renderMarks();
  });

  loadMarks();
}());
