<!DOCTYPE html>
<html>
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Live Programming</title>
  <style>
    body { margin:0; font-family:sans-serif; display:grid; grid-template-columns:2fr 1fr; height:100vh; }
    #left { display:flex; flex-direction:column; }
    #editor { flex:2; }
    #result { flex:1; display:flex; flex-direction:column; background:#111; }
    #output {
      flex:1;
      background:#111;
      color:#0f0;
      margin:0;
      padding:10px;
      overflow:auto;
      white-space:pre-wrap;
      display:block;
    }
    #preview {
      flex:1;
      width:100%;
      height:100%;
      border:0;
      background:white;
      display:none;
    }
    #right { display:flex; flex-direction:column; border-left:1px solid #ccc; }
    #log { flex:1; overflow:auto; padding:10px; white-space:pre-wrap; }
    button { cursor:pointer; }
  </style>
</head>
<body>
  <div id="left">
    <button onclick="runCode()">▶ Run</button>
    <div id="editor"></div>
    <div id="result">
      <pre id="output">Output muncul di sini...</pre>
      <iframe id="preview" title="HTML preview"></iframe>
    </div>
  </div>
  <div id="right">
    <div id="log"></div>
    <input id="msg" placeholder="Tanya AI soal coding..." onkeydown="if(event.key==='Enter')ask()">
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
  <script>
    const token = document.querySelector('meta[name=csrf-token]').content;
    const output = document.getElementById('output');
    const preview = document.getElementById('preview');
    let editor;

    require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
    require(['vs/editor/editor.main'], () => {
      editor = monaco.editor.create(document.getElementById('editor'), {
        value: '<!DOCTYPE html>\n<html>\n  <body>\n    <h1 style="color: #2563eb;">Halo dunia</h1>\n    <button onclick="alert(\'Hello from HTML!\')">Klik saya</button>\n  </body>\n</html>', language: 'html', theme: 'vs-dark'
      });
    });

    function isHtmlLike(code) {
      return /<!doctype html|<html|<body|<div|<section|<p|<h[1-6]|<style|<script|<button|<input/i.test(code);
    }

    async function post(url, body) {
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(body)
      });
      return r.json();
    }

    function showPreview(code) {
      output.style.display = 'none';
      preview.style.display = 'block';
      preview.srcdoc = code;
    }

    function showConsole(text) {
      preview.style.display = 'none';
      output.style.display = 'block';
      output.textContent = text;
    }

    async function runCode() {
      const code = editor.getValue();

      if (isHtmlLike(code)) {
        showPreview(code);
        return;
      }

      showConsole('Menjalankan...');
      const d = await post('/run', { code });
      showConsole(d.output || 'Tidak ada output');
    }

    async function ask() {
      const input = document.getElementById('msg');
      const log = document.getElementById('log');
      log.textContent += '\nKamu: ' + input.value + '\n';
      const d = await post('/chat', { message: input.value });
      log.textContent += 'AI: ' + d.reply + '\n';
      input.value = '';
    }
  </script>
</body>
</html>