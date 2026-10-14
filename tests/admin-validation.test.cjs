const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {test} = require('node:test');
const {validateAdminField: validate} = require('../public/assets/js/admin-validation.js');
const script = fs.readFileSync(require('node:path').join(__dirname,'../public/assets/js/admin-validation.js'),'utf8');

test('checks airline and airport field lengths and codes using server byte limits',()=>{
    for(const kind of ['airlines','airports']){
        const code=kind==='airlines'?'AB':'KHI';
        assert.equal(validate({kind,name:'iata_code',value:code}), '');
        assert.ok(validate({kind,name:'iata_code',value:'12'}));
        assert.equal(validate({kind,name:'icao_code',value:''}), '');
        assert.ok(validate({kind,name:'icao_code',value:'?'}));
        assert.ok(validate({kind,name:'name',value:'   '}));
        assert.equal(validate({kind,name:'name',value:'AeroBook Air'}), '');
        assert.ok(validate({kind,name:'name',value:'é'.repeat(91)}));
    }
    for(const name of ['city','country']){
        assert.ok(validate({kind:'airports',name,value:''}));
        assert.ok(validate({kind:'airports',name,value:'A'.repeat(121)}));
        assert.equal(validate({kind:'airports',name,value:'Karachi'}),'');
    }
    assert.equal(validate({kind:'airports',name:'timezone',value:'Asia/Karachi',zones:['Asia/Karachi']}),'');
    assert.equal(validate({kind:'airports',name:'timezone',value:'+05:00'}),'');
    assert.equal(validate({kind:'airports',name:'timezone',value:'PKT',zones:['pkt']}),'');
    assert.ok(validate({kind:'airports',name:'timezone',value:'Fake/Zone',zones:['UTC']}));
});

test('seat layout requires valid flight, cabin, integer rows and seat letters',()=>{
    for(const value of ['', '0','101','1.5','1e2']) assert.ok(validate({kind:'seats',name:'rows',value}));
    for(const value of ['1','100']) assert.equal(validate({kind:'seats',name:'rows',value}),'');
    assert.ok(validate({kind:'seats',name:'flight_id',value:'0'}));
    assert.equal(validate({kind:'seats',name:'flight_id',value:'1'}),'');
    assert.ok(validate({kind:'seats',name:'cabin_class',value:'unknown'}));
    assert.equal(validate({kind:'seats',name:'cabin_class',value:'premium_economy'}),'');
    assert.ok(validate({kind:'seats',name:'letters[]',value:'A',selectedLetters:[]}));
    assert.ok(validate({kind:'seats',name:'letters[]',value:'A',selectedLetters:['Z']}));
    assert.equal(validate({kind:'seats',name:'letters[]',value:'A',selectedLetters:['A','K']}),'');
});

test('inline flight edits validate times/fares and booking transitions follow server rules',()=>{
    assert.ok(validate({kind:'flights',name:'base_fare',value:'1e3'}));
    assert.equal(validate({kind:'flights',name:'base_fare',value:'18500.00'}),'');
    assert.ok(validate({kind:'flights',name:'arrival_at',value:'2027-02-29T12:00'}));
    assert.ok(validate({kind:'flights',name:'arrival_at',value:'2028-02-29T12:00',values:{departure_at:'2028-02-29T12:00'}}));
    assert.equal(validate({kind:'flights',name:'arrival_at',value:'2028-02-29T12:01',values:{departure_at:'2028-02-29T12:00'}}),'');
    assert.match(validate({kind:'booking_status',name:'status',value:'confirmed',originalStatus:'pending'}),/verifying its payment/);
    assert.equal(validate({kind:'booking_status',name:'status',value:'cancelled',originalStatus:'confirmed'}),'');
    assert.ok(validate({kind:'booking_status',name:'status',value:'pending',originalStatus:'cancelled'}));
    assert.ok(validate({kind:'seat_status',name:'status',value:'invalid'}));
});

test('external record controls show live errors, block submit, and clear on reset',()=>{
    const field=(name,value,type='text')=>({name,value,type,tagName:'INPUT',validity:{},attrs:{},events:{},matches(){return this.type!=='hidden';},setCustomValidity(message){this.message=message;},setAttribute(k,v){this.attrs[k]=v;},getAttribute(k){return this.attrs[k];},removeAttribute(k){delete this.attrs[k];},after(error){this.error=error;},addEventListener(k,v){this.events[k]=v;},focus(){this.focused=true;}});
    const fields=[field('kind','flights','hidden'),field('departure_at','2028-02-29T12:00','datetime-local'),field('arrival_at','2028-02-29T13:00','datetime-local'),field('base_fare','10.00','number')];
    const get=name=>fields.find(field=>field.name===name);
    const form={elements:fields,dataset:{},closest:()=>null,addEventListener(k,v){this[k]=v;}};
    form.elements.namedItem=get;
    vm.runInNewContext(script,{document:{getElementById:()=>null,querySelectorAll:()=>[form],createElement:()=>({setAttribute(){}})},TextEncoder,Date,setTimeout:fn=>fn()});
    get('departure_at').value='2028-02-29T14:00';get('departure_at').events.input();
    assert.match(get('arrival_at').error.textContent,/later than departure/);
    let blocked=false,stopped=false;
    form.submit({preventDefault(){blocked=true;},stopImmediatePropagation(){stopped=true;}});
    assert.ok(blocked&&stopped&&get('arrival_at').focused);
    get('arrival_at').value='2028-02-29T15:00';get('arrival_at').events.change();
    form.submit({preventDefault(){assert.fail('Valid edit blocked');}});
    form.reset();assert.equal(get('arrival_at').error.textContent,'');
});
