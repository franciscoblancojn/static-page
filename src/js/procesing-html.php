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
    // atributos peligrosos
    if (
      script.type === "module" ||
      script.defer ||
      script.async
    ) {
      // return true;
    }

    const blacklist = [
      "elementor",
      ".min.js",
    ];

    return blacklist.some((item) =>
      jsUrl.toLowerCase().includes(item.toLowerCase())
    );
  }
  async function convinarJsExterno(doc, baseUrl) {
    const scripts = [...doc.querySelectorAll("script[src]")];

    let combinedJs = "";

    for (const script of scripts) {
      const src = script.getAttribute("src");

      if (!src) continue;

      try {
        const jsUrl = toAbsoluteUrl(src, baseUrl);

        if (!isWpContent(jsUrl)) {
          continue;
        }

        if (shouldIgnoreScript(script, jsUrl)) {
          console.log("IGNORADO:", jsUrl);
          continue;
        }

        console.log("JS:", jsUrl);

        let jsCode = await getCode(jsUrl);

        // eliminar sourcemaps
        jsCode = jsCode.replace(
          /\/\/# sourceMappingURL=.*$/gm,
          ""
        );

        combinedJs += `
            ;
            ${jsCode}
            ;
        `;

        script.remove();
      } catch (e) {
        console.error("Error JS:", src, e);
      }
    }

    if (combinedJs.trim()) {
      const newScript = doc.createElement("script");

      // SIN regex minify
      newScript.textContent = combinedJs;

      doc.body.appendChild(newScript);
    }
  }

  function convinarJsInterno(doc) {
    /**
     * =========================
     * COMBINAR JS INTERNOS
     * =========================
     */
    const internalScripts = [...doc.querySelectorAll("script:not([src])")];

    let internalJs = "";

    for (const script of internalScripts) {
      const js = script.textContent || "";

      // ignorar scripts vacíos
      if (!js.trim()) {
        script.remove();
        continue;
      }

      internalJs += "\n;\n" + js;

      // eliminar script original
      script.remove();
    }

    /**
     * =========================
     * CREAR SCRIPT FINAL
     * =========================
     */
    if (internalJs.trim()) {
      const finalScript = doc.createElement("script");

      finalScript.textContent = jsMinify(internalJs);

      doc.body.appendChild(finalScript);
    }
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
  async function procesingHtml(html, baseUrl = window.location.href, config = {}) {
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
    if (css) {
      const style = doc.createElement("style");
      const imports = css.match(/@import[^;]+;/g) || [];
      const rest = css.replace(/@import[^;]+;/g, "");
      style.textContent = imports.join("") + "\n" + rest;
      doc.head.appendChild(style);
    }

    if (config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_EXTERNO) {
      await convinarJsExterno(doc, baseUrl);
    }
    if (config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_INTERNO) {
      convinarJsInterno(doc);
    }
    fixLazyImages(doc);

    return "<!DOCTYPE html>\n" + doc.documentElement.outerHTML;
  }
</script>