(function () {
  "use strict";

  var API = "/api";
  var state = { athletes: [], events: [], tracks: [], marks: [], users: [] };
  var currentUser = null;

  function byId(id) { return document.getElementById(id); }
  function t(value) { return window.RankingI18n.t(value); }
  function eventLabel(event) { return t(event.name); }
  function normalized(value) { return String(value || "").trim().toLocaleLowerCase("es"); }
  function localDateValue(date) {
    var offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
  }
  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }
  function normalizedHeader(value) {
    return normalized(value.replace(/^\uFEFF/, "")).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }
  function formatDate(date) {
    if (!date) return "";
    return new Date(date + "T00:00:00").toLocaleDateString(window.RankingI18n.language() === "ca" ? "ca-ES" : "es-ES");
  }
  function athleteLabel(athlete) { return athlete.name + " " + athlete.surname; }
  function trackLabel(track) { return track.name + " - " + track.city; }
  function itemById(items, id) {
    return items.find(function (item) { return String(item.id) === String(id); });
  }
  function itemByLabel(items, input, labelFn) {
    var value = normalized(input);
    return items.find(function (item) { return normalized(labelFn(item)) === value; });
  }
  async function request(path, options) {
    var config = options || {};
    config.headers = { "Content-Type": "application/json", "Accept-Language": window.RankingI18n.language() };
    var response;
    try {
      response = await fetch(API + path, config);
    } catch (error) {
      throw new Error(t("No se puede conectar con el servidor de datos."));
    }
    var data = await response.json().catch(function () { return {}; });
    if (response.status === 401 && path !== "/auth/login") {
      showAuthentication(false);
    }
    if (!response.ok) throw new Error(t(data.error || "No se ha podido guardar la informacion."));
    return data;
  }
  function showAppError(message) {
    byId("app-error").textContent = t(message || "");
    byId("app-error").classList.toggle("hidden", !message);
  }
  function showAuthentication(setupRequired) {
    currentUser = null;
    byId("auth-shell").classList.remove("hidden");
    byId("app-header").classList.add("hidden");
    byId("app-shell").classList.add("hidden");
    byId("app-footer").classList.add("hidden");
    byId("login-form").classList.toggle("hidden", setupRequired);
    byId("setup-form").classList.toggle("hidden", !setupRequired);
  }
  function showApplication(user) {
    currentUser = user;
    byId("auth-shell").classList.add("hidden");
    byId("app-header").classList.remove("hidden");
    byId("app-shell").classList.remove("hidden");
    byId("app-footer").classList.remove("hidden");
    byId("current-user-name").textContent = user.name;
  }
  async function initializeSession() {
    try {
      var auth = await request("/auth/status");
      if (auth.user) {
        showApplication(auth.user);
        await refreshData();
      } else {
        showAuthentication(auth.setupRequired);
      }
    } catch (error) {
      showAuthentication(false);
      byId("login-error").textContent = error.message;
    }
  }
  function showImportStatus(message, failure) {
    byId("athlete-import-status").textContent = t(message);
    byId("athlete-import-status").classList.remove("hidden");
    byId("athlete-import-status").classList.toggle("failure", Boolean(failure));
  }
  async function refreshData() {
    try {
      state = await request("/bootstrap");
      var updatedUser = currentUser && itemById(state.users, currentUser.id);
      if (updatedUser) showApplication(updatedUser);
      showAppError("");
      render();
    } catch (error) {
      showAppError(error.message);
    }
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

  function parseCsv(text, delimiter) {
    var rows = [], row = [], field = "", quoted = false;
    for (var index = 0; index < text.length; index += 1) {
      var character = text[index];
      if (quoted) {
        if (character === '"' && text[index + 1] === '"') {
          field += '"';
          index += 1;
        } else if (character === '"') {
          quoted = false;
        } else {
          field += character;
        }
      } else if (character === '"') {
        quoted = true;
      } else if (character === delimiter) {
        row.push(field);
        field = "";
      } else if (character === "\n") {
        row.push(field.replace(/\r$/, ""));
        rows.push(row);
        row = [];
        field = "";
      } else {
        field += character;
      }
    }
    if (quoted) throw new Error("El archivo contiene comillas sin cerrar.");
    if (field || row.length) {
      row.push(field.replace(/\r$/, ""));
      rows.push(row);
    }
    return rows;
  }
  function csvRows(text) {
    var semicolonRows = parseCsv(text, ";");
    var commaRows = parseCsv(text, ",");
    return (semicolonRows[0] || []).length >= (commaRows[0] || []).length ? semicolonRows : commaRows;
  }
  function normalizeImportedDate(value) {
    var date = value.trim();
    var european = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(date);
    if (european) date = european[3] + "-" + european[2] + "-" + european[1];
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return null;
    var parts = date.split("-").map(Number);
    var parsed = new Date(parts[0], parts[1] - 1, parts[2]);
    if (parsed.getFullYear() !== parts[0] || parsed.getMonth() !== parts[1] - 1 ||
        parsed.getDate() !== parts[2] || date > localDateValue(new Date())) return null;
    return date;
  }
  function downloadAthleteTemplate() {
    var csv = "\uFEFFNombre;Apellidos;Fecha de nacimiento\nAna;Garcia Lopez;2010-05-18\n";
    var link = document.createElement("a");
    link.href = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
    link.download = "plantilla-atletas.csv";
    link.click();
    URL.revokeObjectURL(link.href);
  }
  async function importAthletes(text) {
    var rows = csvRows(text).filter(function (row) {
      return row.some(function (value) { return value.trim(); });
    });
    if (!rows.length) throw new Error("El archivo CSV esta vacio.");
    var headers = rows[0].map(normalizedHeader);
    var nameIndex = headers.indexOf("nombre");
    var surnameIndex = headers.indexOf("apellidos");
    var birthdateIndex = headers.indexOf("fecha de nacimiento");
    if (birthdateIndex < 0) birthdateIndex = headers.indexOf("fecha_nacimiento");
    if (nameIndex < 0 || surnameIndex < 0 || birthdateIndex < 0) {
      throw new Error("Faltan las columnas Nombre, Apellidos o Fecha de nacimiento.");
    }
    var invalid = 0;
    var athletes = [];
    rows.slice(1).forEach(function (row) {
      var name = (row[nameIndex] || "").trim();
      var surname = (row[surnameIndex] || "").trim();
      var birthdate = normalizeImportedDate(row[birthdateIndex] || "");
      if (!name || !surname || !birthdate) {
        invalid += 1;
      } else {
        athletes.push({ name: name, surname: surname, birthdate: birthdate });
      }
    });
    var result = await request("/athletes/import", { method: "POST", body: JSON.stringify({ athletes: athletes }) });
    await refreshData();
    invalid += result.invalid;
    var message = result.imported + " " + t("atletas importados");
    if (result.duplicates) message += ", " + result.duplicates + " " + t("duplicados omitidos");
    if (invalid) message += ", " + invalid + " " + t("filas no validas omitidas");
    showImportStatus(message + ".", !result.imported && invalid > 0);
  }

  function setError(name, message) { byId(name + "-error").textContent = t(message || ""); }
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
      return "<option value=\"" + escapeHtml(eventLabel(event)) + "\"></option>";
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
        "<button class=\"row-button\" data-edit-athlete=\"" + athlete.id + "\">" + t("Editar") + "</button>" +
        "<button class=\"row-button danger\" data-delete-athlete=\"" + athlete.id + "\">" + t("Eliminar") + "</button></td></tr>";
    }).join("");
    byId("athletes-empty").classList.toggle("hidden", state.athletes.length > 0);
  }
  function renderEvents() {
    byId("events-body").innerHTML = state.events.slice().sort(function (a, b) {
      return a.name.localeCompare(b.name, "es");
    }).map(function (event) {
      var direction = event.resultDirection === "higher" ? t("Marca mas alta") : t("Marca mas baja");
      return "<tr><td><strong>" + escapeHtml(eventLabel(event)) + "</strong></td><td>" +
        direction + "</td><td class=\"row-actions\">" +
        "<button class=\"row-button\" data-edit-event=\"" + event.id +
        "\">" + t("Editar") + "</button><button class=\"row-button danger\" data-delete-event=\"" + event.id +
        "\">" + t("Eliminar") + "</button></td></tr>";
    }).join("");
    byId("events-empty").classList.toggle("hidden", state.events.length > 0);
  }
  function renderTracks() {
    byId("tracks-body").innerHTML = state.tracks.slice().sort(function (a, b) {
      return trackLabel(a).localeCompare(trackLabel(b), "es");
    }).map(function (track) {
      return "<tr><td><strong>" + escapeHtml(track.name) + "</strong></td><td>" + escapeHtml(track.city) +
        "</td><td class=\"row-actions\"><button class=\"row-button\" data-edit-track=\"" + track.id +
        "\">" + t("Editar") + "</button><button class=\"row-button danger\" data-delete-track=\"" + track.id +
        "\">" + t("Eliminar") + "</button></td></tr>";
    }).join("");
    byId("tracks-empty").classList.toggle("hidden", state.tracks.length > 0);
  }
  function renderUsers() {
    byId("users-body").innerHTML = state.users.slice().sort(function (a, b) {
      return a.name.localeCompare(b.name, "es");
    }).map(function (user) {
      var stateClass = user.active ? "state" : "state inactive";
      var stateLabel = t(user.active ? "Activo" : "Inactivo");
      return "<tr><td><strong>" + escapeHtml(user.name) + "</strong></td><td>" +
        escapeHtml(user.username) + "</td><td><span class=\"" + stateClass + "\">" +
        stateLabel + "</span></td><td class=\"row-actions\">" +
        "<button class=\"row-button\" data-edit-user=\"" + user.id + "\">" + t("Editar") + "</button>" +
        "<button class=\"row-button danger\" data-delete-user=\"" + user.id +
        "\">" + t("Eliminar") + "</button></td></tr>";
    }).join("");
    byId("users-empty").classList.toggle("hidden", state.users.length > 0);
  }
  function filteredAthleteMarks() {
    var query = normalized(byId("marks-athlete-filter").value);
    if (!query) return [];
    var athleteIds = state.athletes.filter(function (athlete) {
      return normalized(athleteLabel(athlete)).includes(query);
    }).map(function (athlete) { return String(athlete.id); });
    return state.marks.filter(function (mark) {
      return athleteIds.includes(String(mark.athleteId));
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
      return "<tr><td><strong>" + escapeHtml(eventLabel(event)) + "</strong></td><td>" +
        escapeHtml(mark.result) + "</td><td>" + escapeHtml(mark.category) + "</td><td>" +
        formatDate(mark.date) + "</td><td>" + escapeHtml(trackLabel(track)) +
        "</td><td class=\"row-actions\"><button class=\"row-button\" data-edit-mark=\"" + mark.id +
        "\">" + t("Editar") + "</button><button class=\"row-button danger\" data-delete-mark=\"" + mark.id +
        "\">" + t("Eliminar") + "</button></td></tr>";
    }).join("");
    var empty = byId("marks-empty");
    empty.classList.toggle("hidden", marks.length > 0);
    empty.textContent = t(query ? "No hay marcas registradas para la busqueda indicada." :
      "Busca un atleta para consultar sus marcas.");
  }
  function render() {
    renderOptions();
    renderAthletes();
    renderEvents();
    renderTracks();
    renderUsers();
    renderMarks();
    byId("count-athletes").textContent = state.athletes.length;
    byId("count-events").textContent = state.events.length;
    byId("count-marks").textContent = state.marks.length;
    updateCategoryPreview();
  }
  function updateCategoryPreview() {
    var athlete = itemByLabel(state.athletes, byId("mark-athlete").value, athleteLabel);
    var date = byId("mark-date").value;
    var display = t("Selecciona atleta y fecha");
    if (athlete && date) display = markCategory(athlete, date) || t("Fecha anterior al nacimiento");
    byId("mark-category").textContent = display;
  }
  function resetForm(prefix) {
    byId(prefix + "-form").reset();
    setError(prefix, "");
    if (prefix === "mark") {
      byId("mark-id").value = "";
      byId("mark-date").value = localDateValue(new Date());
      byId("mark-form-title").textContent = t("Registrar marca");
      byId("mark-submit").textContent = t("Guardar marca");
      byId("cancel-mark").classList.add("hidden");
      updateCategoryPreview();
      return;
    }
    if (prefix === "user") {
      byId("user-password").required = true;
      byId("user-active").checked = true;
    }
    byId(prefix + "-id").value = "";
    byId("cancel-" + prefix).classList.add("hidden");
  }

  async function saveAthlete(event) {
    event.preventDefault();
    var id = byId("athlete-id").value;
    var payload = {
      name: byId("athlete-name").value.trim(),
      surname: byId("athlete-surname").value.trim(),
      birthdate: byId("athlete-birthdate").value
    };
    try {
      await request("/athletes" + (id ? "/" + id : ""), {
        method: id ? "PUT" : "POST", body: JSON.stringify(payload)
      });
      resetForm("athlete");
      await refreshData();
    } catch (error) {
      setError("athlete", error.message);
    }
  }
  async function saveEvent(event) {
    event.preventDefault();
    var id = byId("event-id").value;
    try {
      await request("/events" + (id ? "/" + id : ""), {
        method: id ? "PUT" : "POST", body: JSON.stringify({
          name: byId("event-name").value.trim(),
          resultDirection: byId("event-direction").value
        })
      });
      resetForm("event");
      await refreshData();
    } catch (error) {
      setError("event", error.message);
    }
  }
  async function saveTrack(event) {
    event.preventDefault();
    var id = byId("track-id").value;
    try {
      await request("/tracks" + (id ? "/" + id : ""), {
        method: id ? "PUT" : "POST",
        body: JSON.stringify({ name: byId("track-name").value.trim(), city: byId("track-city").value.trim() })
      });
      resetForm("track");
      await refreshData();
    } catch (error) {
      setError("track", error.message);
    }
  }
  async function saveUser(event) {
    event.preventDefault();
    var id = byId("user-id").value;
    var password = byId("user-password").value;
    var payload = {
      name: byId("user-name").value.trim(),
      username: byId("user-username").value.trim(),
      active: byId("user-active").checked
    };
    if (!id || password) payload.password = password;
    try {
      await request("/users" + (id ? "/" + id : ""), {
        method: id ? "PUT" : "POST",
        body: JSON.stringify(payload)
      });
      resetForm("user");
      await refreshData();
    } catch (error) {
      setError("user", error.message);
    }
  }

  async function saveMark(event) {
    event.preventDefault();
    var athlete = itemByLabel(state.athletes, byId("mark-athlete").value, athleteLabel);
    var competition = itemByLabel(state.events, byId("mark-event").value, eventLabel);
    var track = itemByLabel(state.tracks, byId("mark-track").value, trackLabel);
    var result = byId("mark-result").value.trim();
    var date = byId("mark-date").value;
    if (!athlete || !competition || !track) {
      setError("mark", "Selecciona un atleta, una prueba y una pista existentes.");
      return;
    }
    if (!markCategory(athlete, date)) {
      setError("mark", "La fecha de la marca no puede ser anterior al nacimiento.");
      return;
    }
    if (parseResult(result) === null) {
      setError("mark", "Indica una marca numerica, por ejemplo 12.43 o 1:58.20.");
      return;
    }
    var id = byId("mark-id").value;
    try {
      await request("/marks" + (id ? "/" + id : ""), {
        method: id ? "PUT" : "POST",
        body: JSON.stringify({
          athleteId: athlete.id, eventId: competition.id, trackId: track.id, date: date, result: result
        })
      });
      resetForm("mark");
      await refreshData();
    } catch (error) {
      setError("mark", error.message);
    }
  }
  function editMaster(prefix, id) {
    var items = { athlete: state.athletes, event: state.events, track: state.tracks };
    var item = itemById(items[prefix], id);
    if (!item) return;
    if (prefix === "athlete") {
      byId("athlete-name").value = item.name;
      byId("athlete-surname").value = item.surname;
      byId("athlete-birthdate").value = item.birthdate;
    } else if (prefix === "event") {
      byId("event-name").value = item.name;
      byId("event-direction").value = item.resultDirection || "lower";
    } else {
      byId("track-name").value = item.name;
      byId("track-city").value = item.city;
    }
    byId(prefix + "-id").value = id;
    byId("cancel-" + prefix).classList.remove("hidden");
    setError(prefix, "");
  }
  function editUser(id) {
    var user = itemById(state.users, id);
    if (!user) return;
    byId("user-id").value = user.id;
    byId("user-name").value = user.name;
    byId("user-username").value = user.username;
    byId("user-password").value = "";
    byId("user-password").required = false;
    byId("user-active").checked = user.active;
    byId("cancel-user").classList.remove("hidden");
    setError("user", "");
  }

  async function deleteItem(resource, id, errorPrefix) {
    try {
      await request("/" + resource + "/" + id, { method: "DELETE" });
      resetForm(errorPrefix);
      await refreshData();
    } catch (error) {
      setError(errorPrefix, error.message);
    }
  }
  function editMark(id) {
    var mark = itemById(state.marks, id);
    var athlete = mark && itemById(state.athletes, mark.athleteId);
    var event = mark && itemById(state.events, mark.eventId);
    var track = mark && itemById(state.tracks, mark.trackId);
    if (!mark || !athlete || !event || !track) return;
    byId("mark-id").value = mark.id;
    byId("mark-athlete").value = athleteLabel(athlete);
    byId("mark-event").value = eventLabel(event);
    byId("mark-track").value = trackLabel(track);
    byId("mark-date").value = mark.date;
    byId("mark-result").value = mark.result;
    byId("mark-form-title").textContent = t("Editar marca");
    byId("mark-submit").textContent = t("Actualizar marca");
    byId("cancel-mark").classList.remove("hidden");
    setError("mark", "");
    updateCategoryPreview();
    switchPanel("marcas");
  }
  async function deleteMark(id) {
    try {
      await request("/marks/" + id, { method: "DELETE" });
      if (byId("mark-id").value === String(id)) resetForm("mark");
      await refreshData();
    } catch (error) {
      showAppError(error.message);
    }
  }

  async function authenticate(endpoint, payload, prefix) {
    try {
      var result = await request(endpoint, { method: "POST", body: JSON.stringify(payload) });
      showApplication(result.user);
      byId(prefix + "-form").reset();
      setError(prefix, "");
      await refreshData();
    } catch (error) {
      setError(prefix, error.message);
    }
  }
  async function logout() {
    try {
      await request("/auth/logout", { method: "POST", body: "{}" });
      showAuthentication(false);
      byId("login-form").reset();
      setError("login", "");
    } catch (error) {
      showAppError(error.message);
    }
  }

  document.querySelector(".tabs").addEventListener("click", function (event) {
    if (event.target.dataset.panel) switchPanel(event.target.dataset.panel);
  });
  byId("login-form").addEventListener("submit", function (event) {
    event.preventDefault();
    authenticate("/auth/login", {
      username: byId("login-username").value.trim(),
      password: byId("login-password").value
    }, "login");
  });
  byId("setup-form").addEventListener("submit", function (event) {
    event.preventDefault();
    authenticate("/auth/setup", {
      name: byId("setup-name").value.trim(),
      username: byId("setup-username").value.trim(),
      password: byId("setup-password").value
    }, "setup");
  });
  byId("logout").addEventListener("click", logout);
  byId("athlete-form").addEventListener("submit", saveAthlete);
  byId("event-form").addEventListener("submit", saveEvent);
  byId("track-form").addEventListener("submit", saveTrack);
  byId("mark-form").addEventListener("submit", saveMark);
  byId("user-form").addEventListener("submit", saveUser);
  ["athlete", "event", "track", "mark", "user"].forEach(function (prefix) {
    byId("cancel-" + prefix).addEventListener("click", function () { resetForm(prefix); });
  });
  [byId("mark-athlete"), byId("mark-date")].forEach(function (input) {
    input.addEventListener("input", updateCategoryPreview);
  });
  byId("marks-athlete-filter").addEventListener("input", renderMarks);
  document.addEventListener("rankinglanguagechange", function () {
    render();
  });
  byId("download-athlete-template").addEventListener("click", downloadAthleteTemplate);
  byId("import-athletes-file").addEventListener("change", function (event) {
    var file = event.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.addEventListener("load", async function () {
      try {
        await importAthletes(String(reader.result));
      } catch (error) {
        showImportStatus(error.message, true);
      }
      byId("import-athletes-file").value = "";
    });
    reader.addEventListener("error", function () {
      showImportStatus("No se ha podido leer el archivo CSV.", true);
      byId("import-athletes-file").value = "";
    });
    reader.readAsText(file);
  });
  document.body.addEventListener("click", function (event) {
    var data = event.target.dataset;
    if (data.editAthlete) { editMaster("athlete", data.editAthlete); switchPanel("atletas"); }
    if (data.editEvent) { editMaster("event", data.editEvent); switchPanel("pruebas"); }
    if (data.editTrack) { editMaster("track", data.editTrack); switchPanel("pistas"); }
    if (data.editUser) { editUser(data.editUser); switchPanel("usuarios"); }
    if (data.deleteAthlete) deleteItem("athletes", data.deleteAthlete, "athlete");
    if (data.deleteEvent) deleteItem("events", data.deleteEvent, "event");
    if (data.deleteTrack) deleteItem("tracks", data.deleteTrack, "track");
    if (data.deleteUser) deleteItem("users", data.deleteUser, "user");
    if (data.editMark) editMark(data.editMark);
    if (data.deleteMark) deleteMark(data.deleteMark);
  });

  byId("mark-date").value = localDateValue(new Date());
  initializeSession();
}());
