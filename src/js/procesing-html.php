<script>
  const stpa_json_config_keys = <?= json_encode(STPA_PAGE_CONFIG::CONFIG) ?>;
  /**
   * Minificador CSS simple sin librerías
   */
  function cssMinfy(css = "") {
    return (
      css

      // eliminar comentarios
      .replace(/\/\*[\s\S]*?\*\//g, "")

      // eliminar saltos de linea
      .replace(/\n/g, "")

      // eliminar tabs
      .replace(/\t/g, "")

      // espacios múltiples
      .replace(/\s+/g, " ")

      // espacios innecesarios (solo fuera de strings)
      .replace(/;\s+/g, ";")
      .replace(/\s*{\s*/g, "{")
      .replace(/\s*}\s*/g, "}")
      .replace(/\s*:\s*/g, ":")
      .replace(/\s*;\s*/g, ";")

      // ; antes de }
      .replace(/;}/g, "}")

      .trim()
    );
  }
  /**
   * Minificador JS simple sin librerías
   */
  function jsMinify(js = "") {
    return js;
    return (
      js

      // eliminar comentarios multilinea
      .replace(/\/\*[\s\S]*?\*\//g, "")

      // eliminar comentarios simples
      .replace(/(^|[^:])\/\/.*$/gm, "$1")

      // eliminar saltos de linea
      .replace(/\n/g, " ")

      // eliminar tabs
      .replace(/\t/g, " ")

      // espacios múltiples
      .replace(/\s+/g, " ")

      // espacios innecesarios
      .replace(/\s*{\s*/g, "{")
      .replace(/\s*}\s*/g, "}")
      .replace(/\s*=\s*/g, "=")
      .replace(/\s*;\s*/g, ";")
      .replace(/\s*,\s*/g, ",")
      .replace(/\s*\(\s*/g, "(")
      .replace(/\s*\)\s*/g, ")")
      .replace(/\s*\+\s*/g, "+")
      .replace(/\s*-\s*/g, "-")
      .replace(/\s*\*\s*/g, "*")
      .replace(/\s*<\s*/g, "<")
      .replace(/\s*>\s*/g, ">")

      .trim()
    );
  }
  /**
   * Minificador HTML simple sin librerías
   */
  function htmlMinify(html = "") {
    return (
      html

      // eliminar comentarios HTML
      .replace(<?= "/<!--[\s\S]*?-->/g" ?>, "")

      // eliminar saltos de linea
      .replace(/\n/g, " ")

      // eliminar tabs
      .replace(/\t/g, " ")

      // espacios múltiples
      .replace(/\s+/g, " ")

      // eliminar espacios entre tags
      .replace(/>\s+</g, "><")

      // eliminar espacios innecesarios
      .trim()
    );
  }
  /**
   * Obtiene el contenido de cualquier archivo desde una URL
   * Sirve para CSS, JS, HTML, etc.
   */
  async function getCode(url) {
    try {
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`Error obteniendo ${url}`);
      }

      return await response.text();
    } catch (error) {
      console.error("getCode error:", error);
      return "";
    }
  }
  /**
   * Convierte URLs relativas en absolutas
   */
  function toAbsoluteUrl(url, baseUrl) {
    return new URL(url, baseUrl).href;
  }
  function stripQueryString(url) {
    return url.split('?')[0];
  }
  async function convinarCssExterno(doc, baseUrl, ignoreList = [], processList = []) {
    const links = doc.querySelectorAll('link[rel="stylesheet"]');

    let css = "";

    for (const link of links) {
      const href = link.getAttribute("href");
      if (!href) continue;

      try {
        const cssUrl = toAbsoluteUrl(href, baseUrl);

        if (ignoreList.some(function(ignored) { return stripQueryString(cssUrl).includes(stripQueryString(ignored)) || stripQueryString(href).includes(stripQueryString(ignored)) })) {
          link.remove();
          continue;
        }

        if (processList.length > 0 && !processList.some(function(p) { return stripQueryString(cssUrl).includes(stripQueryString(p)) || stripQueryString(href).includes(stripQueryString(p)) })) {
          continue;
        }

        css += "\n" + await getCode(cssUrl);
        link.remove();
      } catch (e) {
        console.error("Error CSS:", href, e);
      }
    }

    return cssMinfy(css);
  }

  function purgeCss(css, doc) {
    // Colectar tokens de JS inline y data-* para detectar clases agregadas dinámicamente
    const jsTokens = new Set();

    const JS_PURGE_VALID_TYPES = ["", "text/javascript", "application/javascript"];
    const jsContent = [...doc.querySelectorAll("script")]
      .filter(function(s) {
        return JS_PURGE_VALID_TYPES.includes((s.getAttribute("type") || "").toLowerCase());
      })
      .map(function(s) { return s.textContent || ""; })
      .join(" ");

    const stringRe = /["'`]([^"'`\n\r\\]{1,300})["'`]/g;
    let sm;
    while ((sm = stringRe.exec(jsContent)) !== null) {
      sm[1].split(/[^a-zA-Z0-9_-]+/).forEach(function(token) {
        if (/^-?[a-zA-Z][a-zA-Z0-9_-]{1,80}$/.test(token)) {
          jsTokens.add(token);
        }
      });
    }

    // Escanear atributos data-* (data-toggle, data-class, data-target, etc.)
    doc.querySelectorAll("*").forEach(function(el) {
      [...el.attributes].forEach(function(attr) {
        if (attr.name.startsWith("data-")) {
          attr.value.split(/\s+/).forEach(function(token) {
            const clean = token.replace(/^[.#]/, "");
            if (/^-?[a-zA-Z][a-zA-Z0-9_-]{1,80}$/.test(clean)) {
              jsTokens.add(clean);
            }
          });
        }
      });
    });

    function selectorMentionedInJs(selector) {
      const tokens = [];
      let m;
      const clsRe = /\.(-?[a-zA-Z][\w-]*)/g;
      const idRe = /#([a-zA-Z][\w-]*)/g;
      while ((m = clsRe.exec(selector)) !== null) tokens.push(m[1]);
      while ((m = idRe.exec(selector)) !== null) tokens.push(m[1]);
      return tokens.some(function(t) { return jsTokens.has(t); });
    }

    function cleanSelectorForTest(selector) {
      return selector
        .replace(/::[\w-]+(\([^)]*\))?/g, "")
        // [attr="val"], [attr~="val"], [attr^="val"], etc. → [attr]
        .replace(/\[([^\]=~|^$*\s]+)[~|^$*]?=[^\]]*\]/g, "[$1]")
        .replace(/:[\w-]+\([^)]*\)/g, "")
        .replace(/:[\w-]+/g, "")
        .replace(/\s+/g, " ")
        .trim();
    }

    function isSelectorUsed(selector) {
      if (selectorMentionedInJs(selector)) return true;
      const testSelector = cleanSelectorForTest(selector);
      if (!testSelector || !/[a-zA-Z0-9_\-\[\].#*]/.test(testSelector)) {
        return true;
      }
      try {
        if (doc.querySelector(testSelector) !== null) return true;
      } catch (e) {
        return true;
      }
      // Full selector didn't match — may be a compound/stateful selector where
      // a dynamic state class (added by JS) prevents matching the static DOM.
      // Keep the rule if ANY individual class or ID atom exists in the DOM.
      const atoms = testSelector.match(/[.#][a-zA-Z][a-zA-Z0-9_-]*/g) || [];
      for (const atom of atoms) {
        try {
          if (doc.querySelector(atom) !== null) return true;
        } catch (e) {
          return true;
        }
      }
      return false;
    }

    function parse(input) {
      let result = "";
      let i = 0;

      function readUntilChars(stops) {
        let out = "";
        let inStr = false,
          q = "";
        while (i < input.length) {
          const c = input[i];
          if (inStr) {
            out += c;
            i++;
            if (c === q) inStr = false;
          } else if (c === '"' || c === "'") {
            inStr = true;
            q = c;
            out += c;
            i++;
          } else if (stops.indexOf(c) !== -1) {
            break;
          } else {
            out += c;
            i++;
          }
        }
        return out;
      }

      function readBlock() {
        let out = input[i]; // '{'
        i++;
        let depth = 1;
        let inStr = false,
          q = "";
        while (i < input.length) {
          const c = input[i++];
          if (inStr) {
            out += c;
            if (c === q) inStr = false;
          } else if (c === '"' || c === "'") {
            inStr = true;
            q = c;
            out += c;
          } else if (c === "{") {
            depth++;
            out += c;
          } else if (c === "}") {
            depth--;
            out += c;
            if (depth === 0) return out;
          } else {
            out += c;
          }
        }
        return out;
      }

      while (i < input.length) {
        while (i < input.length && /\s/.test(input[i])) i++;
        if (i >= input.length) break;

        if (input[i] === "@") {
          const atRule = readUntilChars(["{", ";"]);
          if (i >= input.length) {
            result += atRule;
            break;
          }

          if (input[i] === ";") {
            result += atRule + ";";
            i++;
          } else if (input[i] === "{") {
            const keyword = atRule.trim().split(/[\s(]/)[0].toLowerCase();
            const block = readBlock();
            const keepAlways = [
              "@keyframes", "@-webkit-keyframes", "@-moz-keyframes",
              "@-o-keyframes", "@font-face", "@counter-style", "@layer",
            ];
            if (keepAlways.includes(keyword)) {
              result += atRule + block;
            } else if (keyword === "@media" || keyword === "@supports") {
              const inner = block.slice(1, -1);
              const purgedInner = parse(inner);
              if (purgedInner.trim()) {
                result += atRule + "{" + purgedInner + "}";
              }
            } else {
              result += atRule + block;
            }
          }
        } else {
          const selector = readUntilChars(["{"]);
          if (i >= input.length) {
            result += selector;
            break;
          }
          const block = readBlock();
          const trimmed = selector.trim();
          if (trimmed && isSelectorUsed(trimmed)) {
            result += selector + block;
          }
        }
      }

      return result;
    }

    return parse(css);
  }

  function convinarCssInterno(doc) {
    const styles = doc.querySelectorAll("style");

    let css = "";

    for (const style of styles) {
      let content = style.textContent || "";
      content = content.replace(<?= "/<!--|-->/g" ?>, "");
      css += "\n" + content;
      style.remove();
    }

    return cssMinfy(css);
  }

  function shouldIgnoreScript(script, jsUrl) {
    const type = (script.getAttribute("type") || "").toLowerCase();
    if (type === "module") return true;
    if (script.defer || script.async) return true;

    const blacklist = ["elementor", ".min.js"];
    return blacklist.some((item) =>
      jsUrl.toLowerCase().includes(item.toLowerCase())
    );
  }

  const JS_VALID_TYPES = ["", "text/javascript", "application/javascript"];

  async function combinarJs(doc, baseUrl, processExternal, processInternal, ignoreList = [], processList = []) {
    const allScripts = [...doc.querySelectorAll("script")];
    let combinedJs = "";

    for (const script of allScripts) {
      const src = script.getAttribute("src");
      const type = (script.getAttribute("type") || "").toLowerCase();

      // ignorar tipos no-JS: JSON-LD, module, etc.
      if (!JS_VALID_TYPES.includes(type)) continue;

      if (src) {
        if (!processExternal) continue;
        try {
          const jsUrl = toAbsoluteUrl(src, baseUrl);

          if (ignoreList.some(function(ignored) { return stripQueryString(jsUrl).includes(stripQueryString(ignored)) || stripQueryString(src).includes(stripQueryString(ignored)) })) {
            script.remove();
            continue;
          }

          if (processList.length > 0) {
            if (!processList.some(function(p) { return stripQueryString(jsUrl).includes(stripQueryString(p)) || stripQueryString(src).includes(stripQueryString(p)) })) continue;
          } else {
            if (shouldIgnoreScript(script, jsUrl)) {
              console.log("JS IGNORADO:", jsUrl);
              continue;
            }
          }
          console.log("JS:", jsUrl);
          let jsCode = await getCode(jsUrl);
          jsCode = jsCode.replace(/\/\/# sourceMappingURL=.*$/gm, "");
          combinedJs += "\n;\n" + jsCode;
          script.remove();
        } catch (e) {
          console.error("Error JS:", src, e);
        }
      } else {
        if (!processInternal) continue;
        const js = script.textContent || "";
        if (!js.trim()) {
          script.remove();
          continue;
        }
        combinedJs += "\n;\n" + js;
        script.remove();
      }
    }

    // Process JS prefetch/preload links
    var jsLinks = doc.querySelectorAll('link[as="script"]');
    for (var li = 0; li < jsLinks.length; li++) {
      var link = jsLinks[li];
      var href = link.getAttribute("href");
      if (!href) continue;
      if (!processExternal) continue;
      try {
        var jsUrl = toAbsoluteUrl(href, baseUrl);

        if (ignoreList.some(function(ignored) { return stripQueryString(jsUrl).includes(stripQueryString(ignored)) || stripQueryString(href).includes(stripQueryString(ignored)) })) {
          link.remove();
          continue;
        }

        if (processList.length > 0) {
          if (!processList.some(function(p) { return stripQueryString(jsUrl).includes(stripQueryString(p)) || stripQueryString(href).includes(stripQueryString(p)) })) continue;
        } else {
          if (shouldIgnoreScript(link, jsUrl)) {
            console.log("JS PREFETCH IGNORADO:", jsUrl);
            continue;
          }
        }
        console.log("JS PREFETCH:", jsUrl);
        var jsCode = await getCode(jsUrl);
        jsCode = jsCode.replace(/\/\/# sourceMappingURL=.*$/gm, "");
        combinedJs += "\n;\n" + jsCode;
        link.remove();
      } catch (e) {
        console.error("Error JS prefetch:", href, e);
      }
    }

    return combinedJs.trim();
  }

  function eliminarWpadminbar(doc) {
    /**
     * =========================
     * ELIMINAR WP ADMIN BAR
     * =========================
     */
    const wpadminbar = doc.querySelector("#wpadminbar");

    if (wpadminbar) {
      wpadminbar.remove();

      const style = doc.createElement("style");

      style.textContent = "html:not(.t-12345){margin-top:0!important;}";

      doc.head.appendChild(style);
    }
  }
  /**
   * Restaurar imágenes lazy load
   */
  function fixLazyImages(doc) {
    const imgs = [...doc.querySelectorAll("img")];

    for (const img of imgs) {
      const lazySrc =
        img.getAttribute("data-src") ||
        img.getAttribute("data-lazy-src") ||
        img.getAttribute("data-original") ||
        img.getAttribute("data-orig-file") ||
        img.getAttribute("data-large_image");

      if (lazySrc) {
        img.setAttribute("src", lazySrc);
      }

      const lazySrcset =
        img.getAttribute("data-srcset") ||
        img.getAttribute("data-lazy-srcset");

      if (lazySrcset) {
        img.setAttribute("srcset", lazySrcset);
      }

      // limpiar lazy attrs
      img.removeAttribute("loading");
      img.removeAttribute("data-src");
      img.removeAttribute("data-lazy-src");
      img.removeAttribute("data-srcset");
      img.removeAttribute("data-lazy-srcset");

      // clases lazy
      img.classList.remove("lazyload");
      img.classList.remove("lazyloading");
      img.classList.remove("lazy");
      img.classList.remove("lazyloaded");
    }

    // soporte picture/source
    const sources = [...doc.querySelectorAll("source")];

    for (const source of sources) {
      const srcset =
        source.getAttribute("data-srcset") ||
        source.getAttribute("data-lazy-srcset");

      if (srcset) {
        source.setAttribute("srcset", srcset);
      }
    }
  }
  /**
   * Elimina del DOM los <link> y <script> que estén en las listas de ignorados,
   * independientemente de si el procesamiento de CSS/JS está activo.
   */
  function removeIgnoredAssets(doc, baseUrl, cssIgnoreList, jsIgnoreList) {
    if (cssIgnoreList && cssIgnoreList.length) {
      var links = doc.querySelectorAll('link[rel="stylesheet"]');
      for (var i = 0; i < links.length; i++) {
        var href = links[i].getAttribute("href");
        if (!href) continue;
        var absUrl = toAbsoluteUrl(href, baseUrl);
        if (cssIgnoreList.some(function(ignored) { return stripQueryString(absUrl).includes(stripQueryString(ignored)) || stripQueryString(href).includes(stripQueryString(ignored)) })) {
          links[i].remove();
        }
      }
    }
    if (jsIgnoreList && jsIgnoreList.length) {
      var scripts = doc.querySelectorAll('script[src]');
      for (var i = 0; i < scripts.length; i++) {
        var src = scripts[i].getAttribute("src");
        if (!src) continue;
        var absUrl = toAbsoluteUrl(src, baseUrl);
        if (jsIgnoreList.some(function(ignored) { return stripQueryString(absUrl).includes(stripQueryString(ignored)) || stripQueryString(src).includes(stripQueryString(ignored)) })) {
          scripts[i].remove();
        }
      }
      var jsLinks = doc.querySelectorAll('link[as="script"]');
      for (var j = 0; j < jsLinks.length; j++) {
        var href = jsLinks[j].getAttribute("href");
        if (!href) continue;
        var absUrl = toAbsoluteUrl(href, baseUrl);
        if (jsIgnoreList.some(function(ignored) { return stripQueryString(absUrl).includes(stripQueryString(ignored)) || stripQueryString(href).includes(stripQueryString(ignored)) })) {
          jsLinks[j].remove();
        }
      }
    }
  }
  /**
   * Reemplaza <a tabindex="0"> por <span>
   */
  function replaceAnchorsWithTabindexZero(doc) {
    const anchors = doc.querySelectorAll('a[tabindex="0"]');
    for (const anchor of anchors) {
      const span = doc.createElement("span");
      for (const attr of anchor.attributes) {
        if (attr.name !== "tabindex" && attr.name !== "href" && attr.name !== "target" && attr.name !== "rel") {
          span.setAttribute(attr.name, attr.value);
        }
      }
      while (anchor.firstChild) {
        span.appendChild(anchor.firstChild);
      }
      anchor.replaceWith(span);
    }
  }
  /**
   * Procesa un HTML:
   * - Busca <link rel="stylesheet">
   * - Busca <script src="">
   * - Descarga el contenido
   * - Reemplaza por <style> y <script>
   */
  async function procesingHtml(html, baseUrl = window.location.href, config = {}, cssHref = null, jsHref = null) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    eliminarWpadminbar(doc);

    if (!config?.<?= STPA_KEY ?>_PAGE_STATIC_ACTIVE) {
      throw new Error("Activa Carga de Pagina Estatica");
    }

    let css = "";

    const cssIgnoreList = config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_IGNORE_LIST || [];
    const jsIgnoreList = config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_IGNORE_LIST || [];
    const cssProcessList = config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_EXTERNO_PROCESS || [];
    const jsProcessList = config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_EXTERNO_PROCESS || [];

    removeIgnoredAssets(doc, baseUrl, cssIgnoreList, jsIgnoreList);

    if (config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_EXTERNO) {
      css += await convinarCssExterno(doc, baseUrl, cssIgnoreList, cssProcessList);
    }
    if (config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_INTERNO) {
      css += convinarCssInterno(doc);
    }

    css = css.trim();
    if (css && config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_PURGE) {
      css = cssMinfy(css);
      css = purgeCss(css, doc);
      css = css.trim();
    }
    if (css) {
      const imports = css.match(/@import[^;]+;/g) || [];
      const rest = css.replace(/@import[^;]+;/g, "");
      css = imports.join("") + "\n" + rest;

      if (cssHref) {
        const link = doc.createElement("link");
        link.rel = "stylesheet";
        link.href = cssHref;
        doc.head.appendChild(link);
      } else {
        const style = doc.createElement("style");
        style.textContent = css;
        doc.head.appendChild(style);
      }
    }

    const processJsExterno = config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_EXTERNO ?? false;
    const processJsInterno = config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_INTERNO ?? false;

    let js = "";
    if (processJsExterno || processJsInterno) {
      js = await combinarJs(doc, baseUrl, processJsExterno, processJsInterno, jsIgnoreList, jsProcessList);
    }

    if (js) {
      const scriptTag = doc.createElement("script");
      if (jsHref) {
        scriptTag.src = jsHref;
      } else {
        scriptTag.textContent = js;
      }
      doc.body.appendChild(scriptTag);
    }

    fixLazyImages(doc);

    replaceAnchorsWithTabindexZero(doc);

    return {
      html: "<!DOCTYPE html>\n" + doc.documentElement.outerHTML,
      css,
      js
    };
  }
</script>