(function () {
  "use strict";

  var STORAGE_KEY = "ranking-language";
  var dictionaries = {
    ca: {
      "Ranking de Atletismo": "Rànquing d'Atletisme",
      "Idioma": "Idioma",
      "Castellano": "Castellà",
      "Acceso restringido": "Accés restringit",
      "Gestion de datos": "Gestió de dades",
      "Usuario": "Usuari",
      "Contrasena": "Contrasenya",
      "Iniciar sesion": "Inicia sessió",
      "Crea el primer usuario que administrara la entrada de datos.": "Crea el primer usuari que administrarà l'entrada de dades.",
      "Nombre": "Nom",
      "Crear usuario inicial": "Crea l'usuari inicial",
      "Gestion deportiva": "Gestió esportiva",
      "Registra instalaciones, atletas, pruebas y marcas con categoria automatica por edad.": "Registra instal·lacions, atletes, proves i marques amb categoria automàtica per edat.",
      "Atletas": "Atletes",
      "Pruebas": "Proves",
      "Marcas": "Marques",
      "Resumen": "Resum",
      "Cerrar sesion": "Tanca la sessió",
      "Secciones": "Seccions",
      "Marcas registradas": "Marques registrades",
      "Pistas": "Pistes",
      "Usuarios": "Usuaris",
      "Nueva entrada": "Nova entrada",
      "Registrar marca": "Registra marca",
      "Atleta": "Atleta",
      "Prueba": "Prova",
      "Fecha": "Data",
      "Resultado": "Resultat",
      "Sitio / pista": "Lloc / pista",
      "Categoria calculada": "Categoria calculada",
      "Selecciona atleta y fecha": "Selecciona atleta i data",
      "Guardar marca": "Desa la marca",
      "Cancelar": "Cancel·la",
      "Revision": "Revisió",
      "Marcas de un atleta": "Marques d'un atleta",
      "Buscar atleta": "Cerca atleta",
      "Marca": "Marca",
      "Categoria": "Categoria",
      "Sitio": "Lloc",
      "Busca un atleta para consultar sus marcas.": "Cerca un atleta per consultar les seves marques.",
      "Descargar plantilla": "Descarrega la plantilla",
      "Importar CSV": "Importa CSV",
      "Columnas de importacion: Nombre, Apellidos y Fecha de nacimiento (AAAA-MM-DD).": "Columnes d'importació: Nom, Cognoms i Data de naixement (AAAA-MM-DD).",
      "Apellidos": "Cognoms",
      "Fecha de nacimiento": "Data de naixement",
      "Guardar": "Desa",
      "Anade el primer atleta para registrar marcas.": "Afegeix el primer atleta per registrar marques.",
      "Cargar pruebas habituales": "Carrega proves habituals",
      "Crea pruebas o carga un catalogo inicial.": "Crea proves o carrega un catàleg inicial.",
      "Pistas de atletismo": "Pistes d'atletisme",
      "Nombre de la instalacion": "Nom de la instal·lació",
      "Localidad": "Localitat",
      "Pista": "Pista",
      "Registra las pistas donde se realizan las marcas.": "Registra les pistes on es realitzen les marques.",
      "Usuarios de gestion": "Usuaris de gestió",
      "Activo": "Actiu",
      "Inactivo": "Inactiu",
      "Estado": "Estat",
      "Al editar, deja la contrasena vacia para conservar la actual.": "En editar, deixa la contrasenya buida per conservar l'actual.",
      "No hay usuarios registrados.": "No hi ha usuaris registrats.",
      "Datos almacenados en la base de datos MySQL.": "Dades emmagatzemades a la base de dades MySQL.",
      "Editar": "Edita",
      "Eliminar": "Elimina",
      "Actualizar marca": "Actualitza la marca",
      "Fecha anterior al nacimiento": "Data anterior al naixement",
      "No hay marcas registradas para la busqueda indicada.": "No hi ha marques registrades per a la cerca indicada.",
      "atletas importados": "atletes importats",
      "duplicados omitidos": "duplicats omesos",
      "filas no validas omitidas": "files no vàlides omeses",
      "No se puede conectar con el servidor de datos.": "No es pot connectar amb el servidor de dades.",
      "No se ha podido guardar la informacion.": "No s'ha pogut desar la informació.",
      "El archivo contiene comillas sin cerrar.": "El fitxer conté cometes sense tancar.",
      "El archivo CSV esta vacio.": "El fitxer CSV és buit.",
      "Faltan las columnas Nombre, Apellidos o Fecha de nacimiento.": "Falten les columnes Nom, Cognoms o Data de naixement.",
      "Selecciona un atleta, una prueba y una pista existentes.": "Selecciona un atleta, una prova i una pista existents.",
      "La fecha de la marca no puede ser anterior al nacimiento.": "La data de la marca no pot ser anterior al naixement.",
      "Indica una marca numerica, por ejemplo 12.43 o 1:58.20.": "Indica una marca numèrica, per exemple 12.43 o 1:58.20.",
      "No se ha podido leer el archivo CSV.": "No s'ha pogut llegir el fitxer CSV.",
      "Debes iniciar sesion para continuar.": "Has d'iniciar sessió per continuar.",
      "Usuario o contrasena incorrectos.": "Usuari o contrasenya incorrectes.",
      "La configuracion inicial ya se ha realizado.": "La configuració inicial ja s'ha realitzat.",
      "No puedes desactivar tu propio usuario.": "No pots desactivar el teu propi usuari.",
      "No puedes eliminar tu propio usuario.": "No pots eliminar el teu propi usuari.",
      "Debe existir al menos un usuario activo.": "Hi ha d'haver almenys un usuari actiu.",
      "El registro no existe.": "El registre no existeix.",
      "Ruta no encontrada.": "Ruta no trobada.",
      "Error de base de datos.": "Error de base de dades.",
      "Error interno del servidor.": "Error intern del servidor.",
      "Falta configurar config.php en el servidor.": "Falta configurar config.php al servidor.",
      "Buscar atleta...": "Cerca atleta...",
      "Buscar prueba...": "Cerca prova...",
      "Buscar instalacion...": "Cerca instal·lació...",
      "Nombre o apellidos...": "Nom o cognoms...",
      "Ej. operador.marta": "Ex. operador.marta",
      "Minimo 8 caracteres": "Mínim 8 caràcters"
    }
  };
  var storedText = new WeakMap();
  var storedAttributes = new WeakMap();
  var language = localStorage.getItem(STORAGE_KEY) === "ca" ? "ca" : "es";

  function t(value) {
    return (dictionaries[language] && dictionaries[language][value]) || value;
  }
  function translateTextNode(node) {
    if (!storedText.has(node)) storedText.set(node, node.nodeValue);
    var source = storedText.get(node);
    var stripped = source.trim();
    if (!stripped) return;
    node.nodeValue = source.replace(stripped, t(stripped));
  }
  function translateAttribute(element, attribute) {
    var originals = storedAttributes.get(element) || {};
    if (!(attribute in originals)) originals[attribute] = element.getAttribute(attribute);
    storedAttributes.set(element, originals);
    if (originals[attribute]) element.setAttribute(attribute, t(originals[attribute]));
  }
  function apply(root) {
    document.documentElement.lang = language;
    document.title = t("Ranking de Atletismo");
    var walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) translateTextNode(node);
    document.querySelectorAll("[placeholder], [aria-label]").forEach(function (element) {
      if (element.hasAttribute("placeholder")) translateAttribute(element, "placeholder");
      if (element.hasAttribute("aria-label")) translateAttribute(element, "aria-label");
    });
    var selector = document.getElementById("language");
    if (selector) selector.value = language;
  }
  function setLanguage(value) {
    language = value === "ca" ? "ca" : "es";
    localStorage.setItem(STORAGE_KEY, language);
    apply(document.body);
    document.dispatchEvent(new CustomEvent("rankinglanguagechange"));
  }

  window.RankingI18n = {
    apply: apply,
    language: function () { return language; },
    setLanguage: setLanguage,
    t: t
  };

  apply(document.body);
  document.getElementById("language").addEventListener("change", function (event) {
    setLanguage(event.target.value);
  });
}());
