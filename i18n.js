(function () {
  "use strict";

  var STORAGE_KEY = "ranking-language";
  var dictionaries = {
    ca: {
      "Ciudades": "Ciutats",
      "Traducciones": "Traduccions",
      "Ámbito": "Àmbit",
      "Pista Cubierta": "Pista Coberta",
      "Aire Libre": "Aire Lliure",
      "Ruta": "Ruta",
      "Ciudad": "Ciutat",
      "Buscar ciudad...": "Cerca ciutat...",
      "Nombre de la pista de atletismo (opcional)": "Nom de la pista d'atletisme (opcional)",
      "Característica técnica (alçada tanques, pes artefacte, ...)": "Característica tècnica (alçada tanques, pes artefacte, ...)",
      "Sexo": "Sexe",
      "Femenino": "Femení",
      "Master": "Màster",
      "Senior": "Sènior",
      "Masculino": "Masculí",
      "Grupo": "Grup",
      "Requiere característica técnica": "Requereix característica tècnica",
      "Si": "Sí",
      "No": "No",
      "Ciudades españolas": "Ciutats espanyoles",
      "Importar ciudades CSV": "Importa ciutats CSV",
      "Provincia": "Província",
      "Traducciones de literales": "Traduccions de literals",
      "Literal": "Literal",
      "Català": "Català",
      "Castellano": "Castellà",
      "Descargar plantilla de resultados": "Descarrega la plantilla de resultats",
      "Importar resultados CSV": "Importa resultats CSV",
      "Ranking de Atletismo": "Rànquing d'Atletisme",
      "Idioma": "Idioma",
      "Castellano": "Castellà",
      "Acceso restringido": "Accés restringit",
      "Administracion": "Administració",
      "Ultimas marcas registradas": "Últimes marques registrades",
      "Resultados": "Resultats",
      "Rankings": "Rànquings",
      "Ranking": "Rànquing",
      "Ranking por prueba": "Rànquing per prova",
      "Mostrar 20 resultados mas": "Mostra 20 resultats més",
      "No hay marcas registradas para los filtros indicados.": "No hi ha marques registrades per als filtres indicats.",
      "Limpiar filtros": "Neteja els filtres",
      "Todas las pruebas": "Totes les proves",
      "Todas las categorias": "Totes les categories",
      "Buscar historial": "Cerca historial",
      "Ver historial": "Mostra l'historial",
      "Historial de marcas": "Historial de marques",
      "Volver a resultados": "Torna als resultats",
      "No hay marcas en el historial de este atleta.": "No hi ha marques a l'historial d'aquest atleta.",
      "Selecciona un atleta de la lista.": "Selecciona un atleta de la llista.",
      "No hay marcas registradas.": "No hi ha marques registrades.",
      "60 m vallas": "60 m tanques",
      "100 m vallas": "100 m tanques",
      "110 m vallas": "110 m tanques",
      "400 m vallas": "400 m tanques",
      "3.000 m obstaculos": "3.000 m obstacles",
      "Altura": "Alçada",
      "Longitud": "Llargada",
      "Triple salto": "Triple salt",
      "Pertiga": "Perxa",
      "Peso": "Pes",
      "Disco": "Disc",
      "Jabalina": "Javelina",
      "Martillo": "Martell",
      "Decatlon": "Decatló",
      "Heptatlon": "Heptatló",
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
      "Falta configurar el archivo .env en el servidor.": "Falta configurar el fitxer .env al servidor.",
      "Buscar atleta...": "Cerca atleta...",
      "Buscar prueba...": "Cerca prova...",
      "Buscar instalacion...": "Cerca instal·lació...",
      "Nombre o apellidos...": "Nom o cognoms...",
      "Ej. operador.marta": "Ex. operador.marta",
      "Minimo 8 caracteres": "Mínim 8 caràcters"
    }
  };
  var managed = { ca: {}, es: {} };
  var storedText = new WeakMap();
  var storedAttributes = new WeakMap();
  var language = localStorage.getItem(STORAGE_KEY) === "es" ? "es" : "ca";

  function t(value) {
    return (managed[language] && managed[language][value]) || (dictionaries[language] && dictionaries[language][value]) || value;
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
    document.title = t("Ranking de Atletismo") + " | Club Atlètic Castellar";
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
    language = value === "es" ? "es" : "ca";
    localStorage.setItem(STORAGE_KEY, language);
    apply(document.body);
    document.dispatchEvent(new CustomEvent("rankinglanguagechange"));
  }

  window.RankingI18n = {
    apply: apply,
    language: function () { return language; },
    setLanguage: setLanguage,
    setManagedTranslations: function (rows) {
      managed = { ca: {}, es: {} };
      (rows || []).forEach(function (row) { managed.ca[row.literal] = row.ca; managed.es[row.literal] = row.es; });
      apply(document.body);
      document.dispatchEvent(new CustomEvent("rankinglanguagechange"));
    },
    t: t
  };

  apply(document.body);
  document.getElementById("language").addEventListener("change", function (event) {
    setLanguage(event.target.value);
  });
}());
