<!DOCTYPE HTML>
<html>
<head>
<style>
[data-type] {
  transition: .3s;
}
[data-type]:hover {
  background: rgba(0, 255, 0, .2);
}
[data-type] * {
  pointer-events:none;
}
#form-elements {
  border: 1px solid blue;
}
#formbuilder {
  font-size: 0;
}
#formbuilder > * {
  font-size: 16px;
}
#formbuilder, #formbuilder .form-element{
  border:1px solid red;
  min-height:50px;
  padding: 5px;
  background: #fff;
}
#formbuilder .form-element {
  transition: background .3s;
  float: left;
}
#formbuilder .form-element:hover {
  background: rgba(255, 0, 0, .1);
}
.hidden {
  display: none;
}
.modal {
  position: fixed;
  width: 100vw;
  height: 100vh;
  left: 0;
  top: 0;
  background: rgba(0,0,0,.5);
}
.modal .inner {
  box-sizing: border-box;
  padding: 10px;
  position: absolute;
  left: 50%;
  top: 50%;
  width: 600px;
  max-width: 90%;
  transform: translate(-50%, -50%);
  background: #fff;
}
.container {
  border: 1px solid #dedede;
  box-sizing: border-box;
  min-height: 50px;
  padding: 10px;
}
</style>
<script>
FormElementType = {
  'container': `
    <div class="container" style="display:inline-block;width:{{size}}%">
      {{contents}}
    </div>
  `,
  'text': `
    <div class="form-ctrl">
      <label>{{label}}</label>
      <input type="text" value="{{default}}">
    </div>
  `,
  'number': `
    <div class="form-ctrl">
      <label>{{label}}</label>
      <input type="number" min="{{min}}" max="{{max}}" step="{{step}}" value="{{default}}">
    </div>
  `
};

/* Drag & Drop */
function allowDrop(ev) {
  ev.preventDefault();
}
function drag(ev) {
  ev.dataTransfer.setData('type', ev.target.dataset.type);
}
function drop(ev) {
  ev.preventDefault();
  ev.stopPropagation();
  if(ev.target.dataset?._container) {
    var type = ev.dataTransfer.getData('type');
    var elem = createElementFromHTML(FormElementType[type]);
    var data = document.querySelector(`#form-elements [data-type="${type}"]`).dataset;
    var keys = Object.keys(data);
    for(var i = 0; i < keys.length; i++) {
      elem.dataset[ keys[i] ] = data[ keys[i] ];
    }
    elem = createElementFromHTML(jsonToElement(elementToJSON(elem), elem.outerHTML));
    elem.setAttribute('onclick', `openModalFor(this)`);
    ev.target.appendChild(elem);
  }
}
function createElementFromHTML(htmlString) {
  var div = document.createElement('div');
  div.innerHTML = htmlString.trim();
  return div.firstChild; 
}

/* HTML -> JSON */
function formToJSON(elem) {
  var json = [];
  var nodes = elem.childNodes;
  for(var i = 0; i < nodes.length; i++) {
    json.push( elementToJSON(nodes[i]) );
  }
  return json;
}
function elementToJSON(elem) {
  var data = { ...elem.dataset };
  var json = {
    'type': elem.dataset.type,
    'contents': []
  };
  delete data.type;
  json.attributes = data;
  var nodes = elem.childNodes;
  for(var i = 0; i < nodes.length; i++) {
    if(!nodes[i]?.classList?.contains('form-element')) {
      continue;
    }
    json.contents.push( elementToJSON(nodes[i]) );
  }
  return json;
}

/* JSON -> HTML */
function jsonToForm(json) {
  var html = '';
  for(var i = 0; i < json.length; i++) {
    html += jsonToElement( json[i] );
  }
  return html;
}
function jsonToElement(json, html) {
  html = (typeof html === 'undefined' ? FormElementType[ json.type ] : html);
  var attributes = Object.keys(json.attributes);
  for(var i = 0; i < attributes.length; i++) {
    html = html.replace(`{{${attributes[i]}}}`, json.attributes[ attributes[i] ]);
  }
  var contents = '';
  for(var i = 0; i < json.contents.length; i++) {
    contents += jsonToElement( json.contents[i] );
  }
  html = html.replace('{{contents}}', contents);
  return html;
}

/* Modal render */
var opened = null;
function openModalFor(elem) {
  event.stopPropagation();
  opened = elem;
  var html = '';
  var data = elem.dataset;
  var keys = Object.keys(data);
  for(var i = 0; i < keys.length; i++) {
    if(keys[i].indexOf('_') === 0) {
      continue;
    }
    if(keys[i] == 'type') {
      html += `<input type="hidden" name="type" value="${data[keys[i]]}">`;
    }
    else {
      html += `
        <div class="form-ctrl">
          <label>${keys[i]}</label>
          <input type="text" name="${keys[i]}" value="${data[keys[i]]}" oninput="document.getElementById('element-details').classList.add('dirty')">
        </div>
      `;
    }
  }
  html += `
    <div>
      <button id="save-element" onclick="saveFormElement()">Mentés</button>
    </div>
  `;
  document.querySelector('#element-details .inner').innerHTML = html;
  document.getElementById('element-details').classList.remove('hidden');
}
function saveFormElement() {
  event.stopPropagation();
  if(opened == null) {
    return;
  }
  var inputs = document.querySelector('#element-details').querySelectorAll('input');
  for(var i = 0; i < inputs.length; i++) {
    opened.dataset[ inputs[i].name ] = inputs[i].value;
  }
  var new_opened = createElementFromHTML(jsonToElement(elementToJSON(opened)));
  for(var i = 0; i < inputs.length; i++) {
    new_opened.dataset[ inputs[i].name ] = inputs[i].value;
  }
  document.getElementById('element-details').classList.remove('dirty');
  opened.parentNode.replaceChild(new_opened, opened);
  new_opened.setAttribute('onclick', `openModalFor(this)`);
  opened = new_opened;
}
function closeModal(elem) {
  if(event.target === elem){
    var confirmed = true;
    if(elem.classList.contains('dirty')) {
      confirmed = false;
    }
    if(!confirmed) {
      confirmed = confirm('Biztos nem mented el?');
    }
    if(confirmed) {
      opened = null;
      elem.classList.add('hidden');
    }
  }
}
</script>
</head>
<body>

<div id="form-elements">
  <div class="form-element" data-type="container" data-size="50" data-_container="true" draggable="true" ondragstart="drag(event)">Container</div>
  <div class="form-element" data-type="text" data-default="" data-label="" draggable="true" ondragstart="drag(event)">Text</div>
  <div class="form-element" data-type="number" data-default="" data-label="" data-min="0" data-max="100" data-step="1" draggable="true" ondragstart="drag(event)">Number</div>
</div>

<div id="formbuilder" data-_container="true" ondrop="drop(event)" ondragover="allowDrop(event)"></div>

<div id="element-details" class="modal hidden" onclick="closeModal(this)">
  <div class="inner"></div>
</div>

</body>
</html>