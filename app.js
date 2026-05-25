(function () {
  "use strict";

  var STORAGE_KEY = "ranking-atletismo-v1";
  var state = loadData();
  var usualEvents = [
    "60 m", "80 m", "100 m", "200 m", "300 m", "400 m", "600 m", "800 m",
    "1.000 m", "1.500 m", "3.000 m", "5.000 m", "10.000 m", "60 m vallas",
    "100 m vallas", "110 m vallas", "400 m vallas", "3.000 m obstaculos",
    "4 x 100 m", "4 x 400 m", "Altura", "Longitud", "Triple salto", "Pertiga",
    "Peso", "Disco", "Jabalina", "Martillo", "Decatlon", "Heptatlon"
  ];

  function byId(id) { return document.getElementById(id); }
  function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 8); }
  function normalized(value) { return value.trim().toLocaleLowerCase("es"); }
  function localDateValue(date) {
    var offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
  }
  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }
  function loadData() {
    try {
      var stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
      if (stored && stored.athletes && stored.events && stored.tracks && stored.marks) return stored;
    } catch (error) {
      localStorage.removeItem(STORAGE_KEY);
    }
    return { athletes: [], events: [], tracks: [], marks: [] };
  }
  function saveData() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    render();
  }
  function formatDate(date) {
    if (!date) return "";
    return new Date(date + "T00:00:00").toLocaleDateString("es-ES");
  }
  function athleteLabel(athlete) { return athlete.name + " " + athlete.surname; }
  function trackLabel(track) { return track.name + " - " + track.city; }
  function itemById(items, id) { return items.find(function (item) { return item.id === id; }); }
  function itemByLabel(items, input, labelFn) {
    var value = normalized(input);
    return items.find(function (item) { return normalized(labelFn(item)) === value; });
  }

  function ageOnDate(birthdate, date) {
    if (!birthdate || !date || date < birthdate) return null;
    var birth = new Date(birthdate + "T00:00:00");
    var performance = new Date(date + "T00:00:00");
    var age = performance.getFullYear() - birth.getFullYear();
    if (performance.getMonth() < birth.getMonth() ||
        (performance.getMonth() === birth.getMonth() && performance.getDate() < birth.getDate())) age -= 1;
    return age;
  }
  function categoryForAge(age) {
    if (age === null || age < 0) return null;
    if (age < 8) return "sub8";
    if (age < 10) return "sub10";
    if (age < 12) return "sub12";
    if (age < 14) return "sub14";
    if (age < 16) return "sub16";
    if (age < 18) return "sub18";
    if (age < 20) return "sub20";
    if (age < 23) return "sub23";
    if (age < 35) return "senior";
    return "master";
  }
  function markCategory(athlete, date) { return categoryForAge(ageOnDate(athlete.birthdate, date)); }

  function parseResult(result) {
    var clean = result.trim().replace(",", ".").replace(/[^\d:.]/g, "");
    if (!clean) return null;
    var chunks = clean.split(":").map(Number);
    if (chunks.some(function (part) { return isNaN(part); })) return null;
    if (chunks.length === 1) return chunks[0];
    if (chunks.length === 2) return chunks[0] * 60 + chunks[1];
    if (chunks.length === 3) return chunks[0] * 3600 + chunks[1] * 60 + chunks[2];
    return null;
  }

  function setError(name, message) { byId(name + "-error").textContent = message || ""; }
  function switchPanel(name) {
    document.querySelectorAll(".tab").forEach(function (tab) {
      tab.classList.toggle("active", tab.dataset.panel === name);
    });
    document.querySelectorAll(".panel").forEach(function (panel) {
      panel.classList.toggle("active", panel.id === "panel-" + name);
    });
  }

  function renderOptions() {
    byId("athlete-options").innerHTML = state.athletes.map(function (athlete) {
      return "<option value=\"" + escapeHtml(athleteLabel(athlete)) + "\"></option>";
    }).join("");
    byId("event-options").innerHTML = state.events.map(function (event) {
      return "<option value=\"" + escapeHtml(event.name) + "\"></option>";
    }).join("");
    byId("track-options").innerHTML = state.tracks.map(function (track) {
      return "<option value=\"" + escapeHtml(trackLabel(track)) + "\"></option>";
    }).join("");
  }

  function renderAthletes() {
    byId("athletes-body").innerHTML = state.athletes.slice().sort(function (a, b) {
      return athleteLabel(a).localeCompare(athleteLabel(b), "es");
    }).map(function (athlete) {
      return "<tr><td><strong>" + escapeHtml(athleteLabel(athlete)) + "</strong></td><td>" +
        formatDate(athlete.birthdate) + "</td><td class=\"row-actions\">" +
        "<button class=\"row-button\" data-edit-athlete=\"" + athlete.id + "\">Editar</button>" +
        "<button class=\"row-button danger\" data-delete-athlete=\"" + athlete.id + "\">Eliminar</button></td></tr>";
    }).join("");
    byId("athletes-empty").classList.toggle("hidden", state.athletes.length > 0);
  }
  function renderEvents() {
    byId("events-body").innerHTML = state.events.slice().sort(function (a, b) {
      return a.name.localeCompare(b.name, "es");
    }).map(function (event) {
      return "<tr><td><strong>" + escapeHtml(event.name) + "</strong></td><td class=\"row-actions\">" +
        "<button class=\"row-button\" data-edit-event=\"" + event.id +
        "\">Editar</button><button class=\"row-button danger\" data-delete-event=\"" + event.id +
        "\">Eliminar</button></td></tr>";
    }).join("");
    byId("events-empty").classList.toggle("hidden", state.events.length > 0);
  }
  function renderTracks() {
    byId("tracks-body").innerHTML = state.tracks.slice().sort(function (a, b) {
      return trackLabel(a).localeCompare(trackLabel(b), "es");
    }).map(function (track) {
      return "<tr><td><strong>" + escapeHtml(track.name) + "</strong></td><td>" + escapeHtml(track.city) +
        "</td><td class=\"row-actions\"><button class=\"row-button\" data-edit-track=\"" + track.id +
        "\">Editar</button><button class=\"row-button danger\" data-delete-track=\"" + track.id +
        "\">Eliminar</button></td></tr>";
    }).join("");
    byId("tracks-empty").classList.toggle("hidden", state.tracks.length > 0);
  }

  function filteredAthleteMarks() {
    var query = normalized(byId("marks-athlete-filter").value);
    if (!query) return [];
    var athleteIds = state.athletes.filter(function (athlete) {
      return normalized(athleteLabel(athlete)).includes(query);
    }).map(function (athlete) { return athlete.id; });
    return state.marks.filter(function (mark) {
      return athleteIds.includes(mark.athleteId);
    }).sort(function (first, second) {
      return second.date.localeCompare(first.date);
    });
  }

  function renderMarks() {
    var query = normalized(byId("marks-athlete-filter").value);
    var marks = filteredAthleteMarks();
    byId("marks-body").innerHTML = marks.map(function (mark) {
      var event = itemById(state.events, mark.eventId);
      var track = itemById(state.tracks, mark.trackId);
      if (!event || !track) return "";
      return "<tr><td><strong>" + escapeHtml(event.name) + "</strong></td><td>" +
        escapeHtml(mark.result) + "</td><td>" + escapeHtml(mark.category) + "</td><td>" +
        formatDate(mark.date) + "</td><td>" + escapeHtml(trackLabel(track)) +
        "</td><td class=\"row-actions\"><button class=\"row-button\" data-edit-mark=\"" + mark.id +
        "\">Editar</button><button class=\"row-button danger\" data-delete-mark=\"" + mark.id +
        "\">Eliminar</button></td></tr>";
    }).join("");
    var empty = byId("marks-empty");
    empty.classList.toggle("hidden", marks.length > 0);
    if (!query) {
      empty.textContent = "Busca un atleta para consultar sus marcas.";
    } else {
      empty.textContent = "No hay marcas registradas para la busqueda indicada.";
    }
  }

  function render() {
    renderOptions();
    renderAthletes();
    renderEvents();
    renderTracks();
    renderMarks();
    byId("count-athletes").textContent = state.athletes.length;
    byId("count-events").textContent = state.events.length;
    byId("count-marks").textContent = state.marks.length;
    updateCategoryPreview();
  }

  function updateCategoryPreview() {
    var athlete = itemByLabel(state.athletes, byId("mark-athlete").value, athleteLabel);
    var date = byId("mark-date").value;
    var display = "Selecciona atleta y fecha";
    if (athlete && date) {
      var category = markCategory(athlete, date);
      display = category || "Fecha anterior al nacimiento";
    }
    byId("mark-category").textContent = display;
  }
  function resetForm(prefix) {
    byId(prefix + "-form").reset();
    setError(prefix, "");
    if (prefix === "mark") {
      byId("mark-id").value = "";
      byId("mark-date").value = localDateValue(new Date());
      byId("mark-form-title").textContent = "Registrar marca";
      byId("mark-submit").textContent = "Guardar marca";
      byId("cancel-mark").classList.add("hidden");
      updateCategoryPreview();
      return;
    }
    byId(prefix + "-id").value = "";
    byId("cancel-" + prefix).classList.add("hidden");
  }

  function saveAthlete(event) {
    event.preventDefault();
    var id = byId("athlete-id").value;
    var name = byId("athlete-name").value.trim();
    var surname = byId("athlete-surname").value.trim();
    var birthdate = byId("athlete-birthdate").value;
    var full = normalized(name + " " + surname);
    if (birthdate > localDateValue(new Date())) {
      setError("athlete", "La fecha de nacimiento no puede estar en el futuro.");
      return;
    }
    if (id && state.marks.some(function (mark) { return mark.athleteId === id && mark.date < birthdate; })) {
      setError("athlete", "La fecha de nacimiento es posterior a una marca del atleta.");
      return;
    }
    if (state.athletes.some(function (athlete) { return athlete.id !== id && normalized(athleteLabel(athlete)) === full; })) {
      setError("athlete", "Ya existe un atleta con ese nombre y apellidos.");
      return;
    }
    var athlete = id ? itemById(state.athletes, id) : { id: uid() };
    athlete.name = name;
    athlete.surname = surname;
    athlete.birthdate = birthdate;
    if (!id) state.athletes.push(athlete);
    state.marks.filter(function (mark) { return mark.athleteId === athlete.id; }).forEach(function (mark) {
      mark.category = markCategory(athlete, mark.date);
    });
    resetForm("athlete");
    saveData();
  }
  function saveEvent(event) {
    event.preventDefault();
    var id = byId("event-id").value;
    var name = byId("event-name").value.trim();
    if (state.events.some(function (item) { return item.id !== id && normalized(item.name) === normalized(name); })) {
      setError("event", "Ya existe una prueba con este nombre.");
      return;
    }
    var item = id ? itemById(state.events, id) : { id: uid() };
    item.name = name;
    if (!id) state.events.push(item);
    resetForm("event");
    saveData();
  }
  function saveTrack(event) {
    event.preventDefault();
    var id = byId("track-id").value;
    var name = byId("track-name").value.trim();
    var city = byId("track-city").value.trim();
    var label = normalized(name + " - " + city);
    if (state.tracks.some(function (track) { return track.id !== id && normalized(trackLabel(track)) === label; })) {
      setError("track", "Ya existe esta pista en la localidad indicada.");
      return;
    }
    var track = id ? itemById(state.tracks, id) : { id: uid() };
    track.name = name;
    track.city = city;
    if (!id) state.tracks.push(track);
    resetForm("track");
    saveData();
  }
  function saveMark(event) {
    event.preventDefault();
    var athlete = itemByLabel(state.athletes, byId("mark-athlete").value, athleteLabel);
    var competition = itemByLabel(state.events, byId("mark-event").value, function (item) { return item.name; });
    var track = itemByLabel(state.tracks, byId("mark-track").value, trackLabel);
    var date = byId("mark-date").value;
    var result = byId("mark-result").value.trim();
    if (!athlete || !competition || !track) {
      setError("mark", "Selecciona un atleta, una prueba y una pista existentes.");
      return;
    }
    var category = markCategory(athlete, date);
    if (!category) {
      setError("mark", "La fecha de la marca no puede ser anterior al nacimiento.");
      return;
    }
    if (parseResult(result) === null) {
      setError("mark", "Indica una marca numerica, por ejemplo 12.43 o 1:58.20.");
      return;
    }
    var id = byId("mark-id").value;
    var mark = id ? itemById(state.marks, id) : { id: uid() };
    mark.athleteId = athlete.id;
    mark.eventId = competition.id;
    mark.trackId = track.id;
    mark.date = date;
    mark.result = result;
    mark.category = category;
    if (!id) state.marks.push(mark);
    resetForm("mark");
    saveData();
  }

  function editMaster(prefix, id) {
    var item;
    if (prefix === "athlete") {
      item = itemById(state.athletes, id);
      byId("athlete-name").value = item.name;
      byId("athlete-surname").value = item.surname;
      byId("athlete-birthdate").value = item.birthdate;
    } else if (prefix === "event") {
      item = itemById(state.events, id);
      byId("event-name").value = item.name;
    } else {
      item = itemById(state.tracks, id);
      byId("track-name").value = item.name;
      byId("track-city").value = item.city;
    }
    byId(prefix + "-id").value = id;
    byId("cancel-" + prefix).classList.remove("hidden");
    setError(prefix, "");
  }
  function deleteMaster(type, id) {
    var relationships = { athlete: "athleteId", event: "eventId", track: "trackId" };
    if (state.marks.some(function (mark) { return mark[relationships[type]] === id; })) {
      setError(type, "No se puede eliminar porque tiene marcas asociadas.");
      return;
    }
    state[type + "s"] = state[type + "s"].filter(function (item) { return item.id !== id; });
    resetForm(type);
    saveData();
  }
  function editMark(id) {
    var mark = itemById(state.marks, id);
    var athlete = mark && itemById(state.athletes, mark.athleteId);
    var event = mark && itemById(state.events, mark.eventId);
    var track = mark && itemById(state.tracks, mark.trackId);
    if (!mark || !athlete || !event || !track) return;
    byId("mark-id").value = mark.id;
    byId("mark-athlete").value = athleteLabel(athlete);
    byId("mark-event").value = event.name;
    byId("mark-track").value = trackLabel(track);
    byId("mark-date").value = mark.date;
    byId("mark-result").value = mark.result;
    byId("mark-form-title").textContent = "Editar marca";
    byId("mark-submit").textContent = "Actualizar marca";
    byId("cancel-mark").classList.remove("hidden");
    setError("mark", "");
    updateCategoryPreview();
    switchPanel("marcas");
  }
  function deleteMark(id) {
    state.marks = state.marks.filter(function (mark) { return mark.id !== id; });
    if (byId("mark-id").value === id) resetForm("mark");
    saveData();
  }

  document.querySelector(".tabs").addEventListener("click", function (event) {
    if (event.target.dataset.panel) switchPanel(event.target.dataset.panel);
  });
  byId("athlete-form").addEventListener("submit", saveAthlete);
  byId("event-form").addEventListener("submit", saveEvent);
  byId("track-form").addEventListener("submit", saveTrack);
  byId("mark-form").addEventListener("submit", saveMark);
  ["athlete", "event", "track", "mark"].forEach(function (prefix) {
    byId("cancel-" + prefix).addEventListener("click", function () { resetForm(prefix); });
  });
  [byId("mark-athlete"), byId("mark-date")].forEach(function (input) {
    input.addEventListener("input", updateCategoryPreview);
  });
  byId("marks-athlete-filter").addEventListener("input", renderMarks);
  byId("load-events").addEventListener("click", function () {
    usualEvents.forEach(function (input) {
      if (!state.events.some(function (event) { return normalized(event.name) === normalized(input); })) {
        state.events.push({ id: uid(), name: input });
      }
    });
    saveData();
  });
  document.body.addEventListener("click", function (event) {
    var data = event.target.dataset;
    if (data.editAthlete) { editMaster("athlete", data.editAthlete); switchPanel("atletas"); }
    if (data.editEvent) { editMaster("event", data.editEvent); switchPanel("pruebas"); }
    if (data.editTrack) { editMaster("track", data.editTrack); switchPanel("pistas"); }
    if (data.deleteAthlete) deleteMaster("athlete", data.deleteAthlete);
    if (data.deleteEvent) deleteMaster("event", data.deleteEvent);
    if (data.deleteTrack) deleteMaster("track", data.deleteTrack);
    if (data.editMark) editMark(data.editMark);
    if (data.deleteMark) deleteMark(data.deleteMark);
  });

  byId("mark-date").value = localDateValue(new Date());
  render();
}());
