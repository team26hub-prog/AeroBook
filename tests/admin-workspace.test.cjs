const {test} = require('node:test');
const assert = require('node:assert/strict');
const {filterRecords,pageRecords,sortRecords,validateFlightSearch,validateAdminSearch} = require('../public/assets/js/admin-workspace.js');
const rows=[
    {text:'AB101 Karachi Lahore Alice PNR001',status:'pending',cells:['AB101','1200']},
    {text:'AB102 Islamabad Dubai Bob PNR002',status:'submitted',cells:['AB102','200']},
    {text:'AB9 Lahore Karachi Alice PNR003',status:'verified',cells:['AB9','1500']}
];

test('flight search accepts clearing, routes, airline punctuation and international text',()=>{
    for(const query of ['', 'AB101', 'KHI → LHE', 'KHI-LHE', '2026-10-14', 'AeroBook Air', 'Airline (UK) & Partners', "O’Connor Air", 'اسلام آباد', 'A'.repeat(100)]) {
        assert.equal(validateFlightSearch(query),'',query);
    }
});

test('flight search rejects whitespace, symbols, control characters and excessive length',()=>{
    for(const query of ['   ','---','@@@','<script>','AB\n101','AB\t101','AB😀','A'.repeat(101)]) {
        assert.ok(validateFlightSearch(query),query);
    }
    assert.match(validateFlightSearch('A'.repeat(101)),/100 characters/);
});

test('other admin searches accept email and reference formats with the same live limits',()=>{
    for(const section of ['bookings','payments','airlines','airports','seats','dashboard']){
        for(const query of ['', 'alice+booking@example.com', 'PNR_123', 'KHI → LHE']) assert.equal(validateAdminSearch(query,section),'');
        for(const query of ['   ', '---','<script>','A'.repeat(101)]) assert.ok(validateAdminSearch(query,section));
    }
});
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
