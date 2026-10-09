const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/customer-navigation.js'), 'utf8');
function setup() {
    const events = {};
    const classes = new Set();
    const node = key => ({ attributes: {}, focused: false,
        setAttribute(name,value) { this.attributes[name]=value; }, focus() { this.focused=true; },
        addEventListener(name,fn) { events[key+name]=fn; }, querySelectorAll: () => []
    });
    const toggle=node('toggle'), close=node('close'), sidebar=node('sidebar'), backdrop=node('backdrop');
    const body={style:{overflow:''},classList:{contains:key=>classes.has(key),toggle:(key,value)=>value?classes.add(key):classes.delete(key)}};
    const window={innerWidth:390,addEventListener:(name,fn)=>{events[name]=fn;}};
    const document={body,querySelector:selector=>({'.customer-menu-toggle':toggle,'.customer-backdrop':backdrop,'.customer-sidebar-close':close})[selector],getElementById:()=>sidebar,addEventListener:(name,fn)=>{events[name]=fn;}};
    vm.runInNewContext(script,{window,document});
    return {events,body,window,toggle,close,classes};
}
test('desktop resize clears the mobile drawer and scroll lock',()=>{
    const ui=setup();ui.events.toggleclick();
    assert.equal(ui.body.style.overflow,'hidden');
    ui.window.innerWidth=821;ui.events.resize();
    assert.equal(ui.body.style.overflow,'');
    assert.equal(ui.classes.has('customer-nav-open'),false);
    assert.equal(ui.toggle.attributes['aria-expanded'],'false');
    ui.window.innerWidth=390;ui.events.resize();ui.events.toggleclick();
    assert.equal(ui.body.style.overflow,'hidden');
});
test('Escape closes the drawer and returns focus to the menu button',()=>{
    const ui=setup();ui.events.toggleclick();ui.events.keydown({key:'Escape'});
    assert.equal(ui.body.style.overflow,'');assert.equal(ui.toggle.focused,true);
});
test('resizing within mobile leaves the open drawer intact',()=>{
    const ui=setup();ui.events.toggleclick();ui.window.innerWidth=600;ui.events.resize();
    assert.equal(ui.body.style.overflow,'hidden');assert.equal(ui.classes.has('customer-nav-open'),true);
});
