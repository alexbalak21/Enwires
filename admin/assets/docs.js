/* A small, safe Markdown renderer for the admin help page (lisez-moi.php).
 *
 * Supports what README.md uses: headings, paragraphs, **bold**, *italic*,
 * `code`, [links](url), bullet / numbered lists (2 levels), tables,
 * fenced code blocks, blockquotes and horizontal rules.
 * Everything is HTML-escaped first; nothing from the text can inject markup.
 *
 *   EnwDocs.render(markdown, { shift: 1 })  ->  { html, toc: [{level, text, id}] }
 *
 * "shift" lowers heading levels (### becomes <h2> with shift 1).
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.EnwDocs = factory();
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /** Only allow links that are safe: http(s), mailto, tel, #anchors and relative paths. */
  function safeUrl(u) {
    u = String(u).trim();
    if (/^(https?:|mailto:|tel:)/i.test(u)) return u;
    if (/^(#|\/|\.\.?\/)/.test(u)) return u;
    if (/^[a-z0-9_\-.]+(\/[a-z0-9_\-.?=&#]+)*$/i.test(u)) return u;
    return '#';
  }

  function inline(text) {
    var codes = [];
    // 1. code spans first, so nothing inside them is touched
    text = String(text).replace(/`([^`]+)`/g, function (m, c) {
      codes.push('<code>' + esc(c) + '</code>');
      return '\u0000' + (codes.length - 1) + '\u0000';
    });
    // 2. escape everything else
    text = esc(text);
    // 3. links
    text = text.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, label, url) {
      var href = safeUrl(url.replace(/&amp;/g, '&'));
      var external = /^https?:/i.test(href);
      return '<a href="' + esc(href) + '"' + (external ? ' target="_blank" rel="noopener"' : '') + '>' + label + '</a>';
    });
    // 4. bold, then italic
    text = text.replace(/\*\*([^*]+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/(^|[^*\w])\*([^*\s][^*]*?)\*(?!\*)/g, '$1<em>$2</em>');
    // 5. put the code spans back
    return text.replace(/\u0000(\d+)\u0000/g, function (m, i) { return codes[Number(i)]; });
  }

  function plain(text) {
    return String(text).replace(/`/g, '').replace(/\*\*/g, '').replace(/(^|\s)\*([^*]+)\*/g, '$1$2').replace(/\[([^\]]+)\]\([^)]*\)/g, '$1');
  }

  function slugify(text) {
    return plain(text).toLowerCase()
      .replace(/&/g, '')
      .replace(/[^\p{L}\p{N}\s-]/gu, '')
      .trim().replace(/\s+/g, '-');
  }

  function splitRow(line) {
    var s = line.trim();
    if (s.charAt(0) === '|') s = s.slice(1);
    if (s.charAt(s.length - 1) === '|') s = s.slice(0, -1);
    return s.split('|').map(function (c) { return c.trim(); });
  }

  var RE_SEP = /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/;
  var RE_LIST = /^(\s*)([-*]|\d+\.)\s+(.*)$/;
  var RE_FENCE = /^\s*```/;
  var RE_HEAD = /^(#{1,6})\s+(.*?)\s*#*\s*$/;
  var RE_HR = /^\s*(-{3,}|\*{3,}|_{3,})\s*$/;
  var RE_COMMENT = /^\s*<!--.*-->\s*$/;

  function buildList(items, idx, indent) {
    var first = items[idx];
    var tag = first.ordered ? 'ol' : 'ul';
    var html = '<' + tag + (first.ordered && first.num !== 1 ? ' start="' + first.num + '"' : '') + '>';
    while (idx < items.length && items[idx].indent === indent) {
      html += '<li>' + inline(items[idx].text);
      idx++;
      if (idx < items.length && items[idx].indent > indent) {
        var r = buildList(items, idx, items[idx].indent);
        html += r[0];
        idx = r[1];
      }
      html += '</li>';
    }
    html += '</' + tag + '>';
    return [html, idx];
  }

  function render(md, opts) {
    opts = opts || {};
    var shift = opts.shift || 0;
    var tocMax = opts.tocMax || 3;
    var lines = String(md).replace(/\r\n?/g, '\n').split('\n');
    var out = [];
    var toc = [];
    var used = {};
    var i = 0;

    function uniqueId(base) {
      base = base || 'section';
      var id = base, n = 2;
      while (used[id]) id = base + '-' + n++;
      used[id] = true;
      return id;
    }

    function startsBlock(idx) {
      var l = lines[idx];
      if (l === undefined) return true;
      if (/^\s*$/.test(l)) return true;
      if (RE_HEAD.test(l) || RE_FENCE.test(l) || RE_LIST.test(l) || RE_HR.test(l) || RE_COMMENT.test(l)) return true;
      if (/^\s*>/.test(l)) return true;
      if (l.indexOf('|') !== -1 && lines[idx + 1] !== undefined && RE_SEP.test(lines[idx + 1])) return true;
      return false;
    }

    while (i < lines.length) {
      var line = lines[i];

      if (/^\s*$/.test(line) || RE_COMMENT.test(line)) { i++; continue; }

      // fenced code block
      if (RE_FENCE.test(line)) {
        var lang = line.replace(/^\s*```/, '').trim();
        var code = [];
        i++;
        while (i < lines.length && !RE_FENCE.test(lines[i])) { code.push(lines[i]); i++; }
        i++; // closing fence
        out.push('<pre class="doc-code"><code' + (lang ? ' class="lang-' + esc(lang) + '"' : '') + '>' + esc(code.join('\n')) + '</code></pre>');
        continue;
      }

      // heading
      var hm = RE_HEAD.exec(line);
      if (hm) {
        var level = Math.max(1, hm[1].length - shift);
        var id = uniqueId(slugify(hm[2]));
        out.push('<h' + level + ' id="' + esc(id) + '">' + inline(hm[2]) + '</h' + level + '>');
        if (level <= tocMax) toc.push({ level: level, text: plain(hm[2]), id: id });
        i++;
        continue;
      }

      if (RE_HR.test(line)) { out.push('<hr>'); i++; continue; }

      // table
      if (line.indexOf('|') !== -1 && lines[i + 1] !== undefined && RE_SEP.test(lines[i + 1])) {
        var head = splitRow(line);
        i += 2;
        var rows = [];
        while (i < lines.length && lines[i].indexOf('|') !== -1 && !/^\s*$/.test(lines[i])) {
          rows.push(splitRow(lines[i]));
          i++;
        }
        var t = '<div class="doc-table"><table><thead><tr>';
        head.forEach(function (c) { t += '<th>' + inline(c) + '</th>'; });
        t += '</tr></thead><tbody>';
        rows.forEach(function (r) {
          t += '<tr>';
          for (var k = 0; k < head.length; k++) t += '<td>' + inline(r[k] === undefined ? '' : r[k]) + '</td>';
          t += '</tr>';
        });
        t += '</tbody></table></div>';
        out.push(t);
        continue;
      }

      // blockquote
      if (/^\s*>/.test(line)) {
        var q = [];
        while (i < lines.length && /^\s*>/.test(lines[i])) { q.push(lines[i].replace(/^\s*>\s?/, '')); i++; }
        out.push('<blockquote>' + render(q.join('\n'), { shift: 0, tocMax: 0 }).html + '</blockquote>');
        continue;
      }

      // list
      if (RE_LIST.test(line)) {
        var items = [];
        while (i < lines.length) {
          var lm = RE_LIST.exec(lines[i]);
          if (lm) {
            items.push({ indent: lm[1].length, ordered: /\d/.test(lm[2]), num: parseInt(lm[2], 10) || 1, text: lm[3] });
            i++;
            continue;
          }
          if (items.length && /^\s{2,}\S/.test(lines[i]) && !RE_FENCE.test(lines[i]) && !RE_HEAD.test(lines[i])) {
            items[items.length - 1].text += ' ' + lines[i].trim();   // wrapped line of the previous item
            i++;
            continue;
          }
          break;
        }
        var idx = 0;
        while (idx < items.length) {
          var r = buildList(items, idx, items[idx].indent);
          out.push(r[0]);
          idx = r[1];
        }
        continue;
      }

      // paragraph
      var para = [line.trim()];
      i++;
      while (i < lines.length && !startsBlock(i)) { para.push(lines[i].trim()); i++; }
      out.push('<p>' + inline(para.join(' ')) + '</p>');
    }

    return { html: out.join('\n'), toc: toc };
  }

  return { render: render, inline: inline, slugify: slugify };
});
