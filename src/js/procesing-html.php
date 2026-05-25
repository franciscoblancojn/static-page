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
   * Verifica si la URL pertenece a wp-content
   */
  function isWpContent(url) {
    // return true;
    return url.includes("/wp-content/");
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
  async function convinarCssExterno(doc, baseUrl) {
    const links = doc.querySelectorAll('link[rel="stylesheet"]');

    let css = "";

    for (const link of links) {
      const href = link.getAttribute("href");
      if (!href) continue;

      try {
        const cssUrl = toAbsoluteUrl(href, baseUrl);

        if (!isWpContent(cssUrl)) {
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
    function cleanSelectorForTest(selector) {
      return selector
        .replace(/::[\w-]+(\([^)]*\))?/g, "")
        .replace(/:[\w-]+\([^)]*\)/g, "")
        .replace(/:[\w-]+/g, "")
        .replace(/\s+/g, " ")
        .trim();
    }

    function isSelectorUsed(selector) {
      const testSelector = cleanSelectorForTest(selector);
      if (!testSelector || !/[a-zA-Z0-9_\-\[\].#*]/.test(testSelector)) {
        return true;
      }
      try {
        return doc.querySelector(testSelector) !== null;
      } catch (e) {
        return true;
      }
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

    return css;
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

  async function combinarJs(doc, baseUrl, processExternal, processInternal) {
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
          if (!isWpContent(jsUrl)) continue;
          if (shouldIgnoreScript(script, jsUrl)) {
            console.log("JS IGNORADO:", jsUrl);
            continue;
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

    if (config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_EXTERNO) {
      css += await convinarCssExterno(doc, baseUrl);
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
      js = await combinarJs(doc, baseUrl, processJsExterno, processJsInterno);
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

    return {
      html: "<!DOCTYPE html>\n" + doc.documentElement.outerHTML,
      css,
      js
    };
  }
</script>