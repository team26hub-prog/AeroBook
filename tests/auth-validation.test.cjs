const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script=fs.readFileSync(require('node:path').join(__dirname,'../public/assets/js/auth-validation.js'),'utf8');
function setup(register=true){
    const names=register?['full_name','email','password','password_confirmation']:['email','password'];
    const fields=names.map(name=>({name,value:'',type:name.includes('password')?'password':name==='email'?'email':'text',validity:{},attrs:{},events:{},error:{},classList:{toggle(){}},setAttribute(k,v){this.attrs[k]=v;},addEventListener(k,v){this.events[k]=v;},focus(){this.focused=true;}}));
    const get=name=>fields.find(field=>field.name===name);
    const form={action:register?'https://aerobook.test/register':'https://aerobook.test/login',elements:{namedItem:get},querySelectorAll:()=>fields,querySelector:selector=>get(selector.match(/"([^"]+)"/)[1]).error,addEventListener(k,v){this[k]=v;}};
    vm.runInNewContext(script,{document:{querySelectorAll:()=>[form]},CSS:{escape:value=>value},TextEncoder});
    const check=(name,value,valid)=>{const field=get(name);field.value=value;field.events.input();assert.equal(!field.error.textContent,valid,`${name}: ${value.slice(0,30)}`);};
    return{get,form,check};
}
test('validates name, email, and UTF-8 registration password lengths',()=>{
    const {check}=setup();
    check('full_name','   ',false);check('full_name','Name\0',false);check('full_name','A'.repeat(151),false);check('full_name','محمد علی',true);
    check('email','invalid',false);check('email','alice@example.com',true);
    check('password','short',false);check('password','é'.repeat(37),false);check('password','é'.repeat(36),true);check('password','        ',true);
});
test('confirmation is rechecked when the original password changes',()=>{
    const {get,check}=setup();check('password','Password123!',true);check('password_confirmation','Password123!',true);
    check('password','Different123!',true);assert.match(get('password_confirmation').error.textContent,/does not match/);
});
test('login rejects oversized passwords and blocks invalid submission',()=>{
    const {form,get,check}=setup(false);check('password','a'.repeat(4097),false);check('password','valid password',true);check('email','wrong',false);
    let blocked=false;form.submit({preventDefault(){blocked=true;},stopImmediatePropagation(){}});assert.ok(blocked&&get('email').focused);
    check('email','alice@example.com',true);form.submit({preventDefault(){assert.fail('Valid login blocked');}});
});
