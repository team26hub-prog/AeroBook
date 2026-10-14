const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/customer-flight-validation.js'), 'utf8');
function setup() {
    const fields = Object.entries({departure_id:'1', arrival_id:'2', date:'2028-02-29'}).map(([name,value]) => ({
        name,value,min:'2028-02-28',max:'',validity:{},attrs:{},events:{},
        options:[{value:''},{value:'1'},{value:'2'}],
        setCustomValidity(message){this.message=message;},setAttribute(k,v){this.attrs[k]=v;},getAttribute(k){return this.attrs[k];},
        after(error){this.error=error;},addEventListener(k,v){this.events[k]=v;},focus(){this.focused=true;}
    }));
    const get = name => fields.find(field => field.name === name);
    const form = {elements:{namedItem:get},querySelectorAll:()=>fields,addEventListener(k,v){this[k]=v;}};
    vm.runInNewContext(script,{document:{querySelector:()=>form,createElement:()=>({setAttribute(){}})},Date});
    return {get,form};
}
test('checks listed airports and revalidates destination when departure changes',()=>{
    const {get}=setup();
    const departure=get('departure_id'), arrival=get('arrival_id');
    for(const value of ['', '0', '999']){departure.value=value;departure.events.change();assert.ok(departure.message);}
    departure.value='2';departure.events.change();assert.match(arrival.message,/different airports/);
    departure.value='1';departure.events.change();assert.equal(arrival.error.textContent,'');
});
test('checks real dates, past dates and valid leap days',()=>{
    const {get}=setup();const date=get('date');
    for(const value of ['', '2028-02-30', '2028-02-27', '2027-02-29']){date.value=value;date.events.input();assert.ok(date.message,value);}
    date.value='2028-02-29';date.events.change();assert.equal(date.message,'');
});
test('blocks invalid searches and allows corrected searches',()=>{
    const {get,form}=setup();get('arrival_id').value='1';
    let blocked=false,stopped=false;
    form.submit({preventDefault(){blocked=true;},stopImmediatePropagation(){stopped=true;}});
    assert.ok(blocked&&stopped&&get('arrival_id').focused);
    get('arrival_id').value='2';form.submit({preventDefault(){assert.fail('Valid search blocked');}});
});
