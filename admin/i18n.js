(function () {
  "use strict";

  var STORAGE_KEY = "ranking-language";
  var dictionaries = {
    ca: {
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
      "Carga masiva de resultados": "Càrrega massiva de resultats",
      "Descarga la plantilla": "Descarrega la plantilla",
      "Marcas de varios atletas": "Marques de diversos atletes",
      "La plantilla Excel, compatible con LibreOffice, agrupa Ámbito / Grupo y filtra la Prueba; la fecha admite AAAA-MM-DD, DD/MM/AAAA o D/M/AA.": "La plantilla Excel, compatible amb LibreOffice, agrupa Àmbit / Grup i filtra la Prova; la data admet AAAA-MM-DD, DD/MM/AAAA o D/M/AA.",
      "El fichero incluye la columna Atleta con Nombre y Apellidos. Antes de cargar marcas se revisan las altas necesarias.": "El fitxer inclou la columna Atleta amb Nom i Cognoms. Abans de carregar marques es revisen les altes necessàries.",
      "Atletas no encontrados": "Atletes no trobats",
      "Corrige nombre y apellidos para vincular un atleta existente, o completa fecha y sexo para darlo de alta. Las filas no completadas y sus marcas se omitirán.": "Corregeix nom i cognoms per vincular un atleta existent, o completa data i sexe per donar-lo d'alta. Les files no completades i les seves marques s'ometran.",
      "Continuar con la carga": "Continua amb la càrrega",
      "Seleccionar": "Selecciona",
      "Atleta de las marcas": "Atleta de les marques",
      "Dar de alta este atleta": "Dona d’alta aquest atleta",
      "Catálogo reglado de pruebas disponible para introducir marcas.": "Catàleg reglat de proves disponible per introduir marques.",
      "Puedes editar los literales incluidos o añadir otros nuevos.": "Pots editar els literals inclosos o afegir-ne de nous.",
      "No hay pruebas disponibles.": "No hi ha proves disponibles.",
      "Puedes añadir pruebas nuevas y editar o eliminar las existentes.": "Pots afegir proves noves i editar o eliminar les existents.",
      "No hay literales disponibles.": "No hi ha literals disponibles.",
      "Selecciona primero el atleta al que corresponden las marcas.": "Selecciona primer l’atleta al qual corresponen les marques.",
      "Caracteristica tecnica": "Característica tècnica",
      "Dato técnico": "Dada tècnica",
      "Ambito": "Àmbit",
      "Ciudades": "Ciutats",
      "Traducciones": "Traduccions",
      "Ámbito": "Àmbit",
      "Pista Cubierta": "Pista Coberta",
      "Aire Libre": "Aire Lliure",
      "Ruta": "Ruta",
      "Ámbito / Grupo": "Àmbit / Grup",
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
      "Ej. Curses": "Ex. Curses",
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
      "Consulta publica": "Consulta pública",
      "Gestion de datos": "Gestió de dades",
      "Usuario": "Usuari",
      "Contrasena": "Contrasenya",
      "Iniciar sesion": "Inicia sessió",
      "Crea el primer usuario que administrara la entrada de datos.": "Crea el primer usuari que administrarà l'entrada de dades.",
      "Nombre": "Nom",
      "Crear usuario inicial": "Crea l'usuari inicial",
      "Atletas": "Atletes",
      "Pruebas": "Proves",
      "Marcas": "Marques",
      "Resumen": "Resum",
      "Cerrar sesion": "Tanca la sessió",
      "Secciones": "Seccions",
      "Marcas registradas": "Marques registrades",
      "Usuarios": "Usuaris",
      "Nueva entrada": "Nova entrada",
      "Registrar marca": "Registra marca",
      "Atleta": "Atleta",
      "Prueba": "Prova",
      "Fecha": "Data",
      "Resultado": "Resultat",
      "Categoria calculada": "Categoria calculada",
      "Categoría calculada": "Categoria calculada",
      "Selecciona atleta y fecha": "Selecciona atleta i data",
      "Ej. 12.43 o 1:58.20": "Ex. 12.43 o 1:58.20",
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
      "Columnas de importación: Nombre, Apellidos, Fecha de nacimiento (AAAA-MM-DD) y Sexo (masculino/femenino).": "Columnes d’importació: Nom, Cognoms, Data de naixement (AAAA-MM-DD) i Sexe (masculí/femení).",
      "Apellidos": "Cognoms",
      "Fecha de nacimiento": "Data de naixement",
      "Guardar": "Desa",
      "Anade el primer atleta para registrar marcas.": "Afegeix el primer atleta per registrar marques.",
      "Mejor resultado": "Millor resultat",
      "Marca mas baja": "Marca més baixa",
      "Marca mas alta": "Marca més alta",
      "Pista": "Pista",
      "El autocompletado de marcas utiliza este catálogo. Carga el CSV oficial con columnas Ciudad y Provincia.": "L’autocompletament de marques utilitza aquest catàleg. Carrega el CSV oficial amb les columnes Ciutat i Província.",
      "No hay ciudades cargadas.": "No hi ha ciutats carregades.",
      "Categoría": "Categoria",
      "Usuarios de gestion": "Usuaris de gestió",
      "Tipo": "Tipus",
      "Administrador": "Administrador",
      "Usuario normal": "Usuari normal",
      "Permisos por atleta": "Permisos per atleta",
      "Añadir marcas": "Afegir marques",
      "Editar marcas": "Editar marques",
      "Entrada por": "Entrada per",
      "Modificada por": "Modificada per",
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
      "Selecciona un atleta, una prueba y una ciudad existentes.": "Selecciona un atleta, una prova i una ciutat existents.",
      "Selecciona una prueba o una categoria.": "Selecciona una prova o una categoria.",
      "Indica el sexo del atleta.": "Indica el sexe de l’atleta.",
      "El atleta o prueba no existe.": "L’atleta o la prova no existeix.",
      "La caracteristica tecnica es obligatoria para esta prueba.": "La característica tècnica és obligatòria per a aquesta prova.",
      "La fecha de la marca no puede ser anterior al nacimiento.": "La data de la marca no pot ser anterior al naixement.",
      "Indica una marca numerica, por ejemplo 12.43 o 1:58.20.": "Indica una marca numèrica, per exemple 12.43 o 1:58.20.",
      "No se ha podido leer el archivo CSV.": "No s'ha pogut llegir el fitxer CSV.",
      "Debes iniciar sesion para continuar.": "Has d'iniciar sessió per continuar.",
      "No tienes permisos para acceder a este apartado.": "No tens permisos per accedir a aquest apartat.",
      "No tienes permisos sobre este atleta.": "No tens permisos sobre aquest atleta.",
      "No puedes cambiar la marca a otro atleta.": "No pots canviar la marca a un altre atleta.",
      "No puedes quitarte tus propios permisos de administrador.": "No pots treure't els teus propis permisos d'administrador.",
      "Usuario o contrasena incorrectos.": "Usuari o contrasenya incorrectes.",
      "La configuracion inicial ya se ha realizado.": "La configuració inicial ja s'ha realitzat.",
      "No puedes desactivar tu propio usuario.": "No pots desactivar el teu propi usuari.",
      "No puedes eliminar tu propio usuario.": "No pots eliminar el teu propi usuari.",
      "Debe existir al menos un usuario activo.": "Hi ha d'haver almenys un usuari actiu.",
      "Debe existir al menos un administrador activo.": "Hi ha d'haver almenys un administrador actiu.",
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
    availableTranslations: function () {
      return Object.keys(dictionaries.ca || {}).map(function (literal) { return { literal: literal, es: literal, ca: dictionaries.ca[literal] }; });
    },
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
