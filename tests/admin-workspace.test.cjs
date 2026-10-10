const {test} = require('node:test');
const assert = require('node:assert/strict');
const {filterRecords,pageRecords,sortRecords} = require('../public/assets/js/admin-workspace.js');
const rows=[
    {text:'AB101 Karachi Lahore Alice PNR001',status:'pending',cells:['AB101','1200']},
    {text:'AB102 Islamabad Dubai Bob PNR002',status:'submitted',cells:['AB102','200']},
    {text:'AB9 Lahore Karachi Alice PNR003',status:'verified',cells:['AB9','1500']}
];
test('search matches all terms across route and customer, ignoring case and extra spaces',()=>{
    assert.deepEqual(filterRecords(rows,'  alice   KARACHI ',''),[rows[0],rows[2]]);
    assert.deepEqual(filterRecords(rows,'Alice Dubai',''),[]);
});
test('review queue includes pending and submitted, excluding decided payments',()=>{
    assert.deepEqual(filterRecords(rows,'','awaiting_review'),[rows[0],rows[1]]);
    assert.deepEqual(filterRecords(rows,'Alice','verified'),[rows[2]]);
});
test('pagination clamps after filtering and supports all rows and empty results',()=>{
    assert.deepEqual(pageRecords(rows,9,2),{rows:[rows[2]],page:2,pages:2});
    assert.deepEqual(pageRecords([],9,10),{rows:[],page:1,pages:1});
    assert.deepEqual(pageRecords(rows,2,0),{rows,page:1,pages:1});
    assert.deepEqual(pageRecords([rows[0]],2,10),{rows:[rows[0]],page:1,pages:1});
});
test('sorting compares flight numbers and amounts naturally and preserves source order',()=>{
    assert.deepEqual(sortRecords(rows,0,false),[rows[2],rows[0],rows[1]]);
    assert.deepEqual(sortRecords(rows,1,true),[rows[2],rows[0],rows[1]]);
    assert.deepEqual(rows.map(r=>r.cells[0]),['AB101','AB102','AB9']);
});
