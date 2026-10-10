/* Progressive admin workflows: the original forms and POST values stay intact. */
(() => {
    'use strict';
    const content = document.querySelector('.admin-content');
    if (!content) return;
    const make = (tag, className, text) => {
        const node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const feedback = document.getElementById('admin-action-feedback');
    const announce = text => { if (feedback) feedback.textContent = text; };
    const draftKey=`aerobook:admin-draft:${document.body.dataset.backUser || '0'}:${location.pathname}`;
    const friendly = value => String(value).replaceAll('_', ' ').replace(/^./, letter => letter.toUpperCase());
    const labelFor = {name:'Name',iata_code:'IATA code',icao_code:'ICAO code',city:'City',country:'Country',timezone:'Local timezone',departure_at:'Departure',arrival_at:'Arrival',base_fare:'Fare per passenger',status:'Status'};

    // Readable lists first; edits remain available without JavaScript.
    content.querySelectorAll('.admin-record-form').forEach(form => {
        const row = form.closest('tr');
        const controls = [...form.elements].filter(control => control.matches('input:not([type=hidden]),select'));
        const save = form.querySelector('button[type=submit]');
        if (!row || !save || !controls.length) return;
        const summaries = [];
        controls.forEach(control => {
            const field = control.closest('.admin-cell-field') || control;
            const text = make('span', 'admin-read-value');
            const cell = control.closest('td');
            const multiple = cell.querySelectorAll('input:not([type=hidden]),select').length > 1;
            const update = () => {
                const value = control.tagName === 'SELECT' ? control.selectedOptions[0]?.textContent : control.value;
                const formatted = control.type === 'datetime-local' ? value?.replace('T',' · ') : control.tagName === 'SELECT' ? friendly(value) : value;
                text.textContent = (multiple ? `${labelFor[control.name] || friendly(control.name)}: ` : '') + (formatted || 'Not provided');
            };
            update();field.before(text);
            summaries.push({field,text,update});
        });
        const edit = make('button', 'small-button admin-edit-button', 'Edit');edit.type='button';
        const cancel = make('button', 'admin-reset admin-edit-cancel', 'Cancel editing');cancel.type='button';
        const identity = row.querySelector('strong')?.textContent || controls.find(control=>control.name==='name')?.value || 'this record';
        edit.setAttribute('aria-label', `Edit ${identity}`);
        edit.title='Open this record for editing. Changes are saved only when you select Save changes.';
        const setEditing = editing => {
            row.classList.toggle('admin-row-editing', editing);
            summaries.forEach(({field,text})=>{field.hidden=!editing;text.hidden=editing;});
            edit.hidden=editing;save.hidden=!editing;cancel.hidden=!editing;
            edit.setAttribute('aria-expanded', String(editing));
        };
        save.before(edit);save.after(cancel);setEditing(false);
        edit.addEventListener('click',()=>{setEditing(true);controls[0].focus();announce(`Editing ${identity}. Save changes to apply your edits, or cancel to keep the original details.`);});
        cancel.addEventListener('click',()=>{
            form.reset();delete form.dataset.confirmed;
            row.classList.remove('admin-row-dirty');
            const note=row.querySelector('.admin-unsaved');if(note)note.textContent='';
            summaries.forEach(item=>item.update());setEditing(false);edit.focus();announce('Editing cancelled. The original details are unchanged.');
        });
    });

    const createPanel = document.getElementById('admin-create');
    const openCreate = () => {
        if (!createPanel) return;
        createPanel.open=true;
        // Anchor navigation can take focus after the click handler completes.
        setTimeout(()=>createPanel.querySelector('input:not([type=hidden]),select')?.focus({preventScroll:true}),0);
    };
    document.querySelectorAll('a[href="#admin-create"]').forEach(link=>link.addEventListener('click',openCreate));
    if (location.hash === '#admin-create') openCreate();
    window.addEventListener('hashchange',()=>{if(location.hash==='#admin-create')openCreate();});
    content.querySelector('.admin-analytics-details')?.addEventListener('toggle',event=>{
        if(event.target.open)window.dispatchEvent(new Event('resize'));
    });

    content.querySelectorAll('select').forEach(select=>{
        if (select.name === 'status' || select.name === 'cabin_class') {
            [...select.options].forEach(option=>{const value=option.value;option.value=value;option.textContent=friendly(value);});
        }
    });
    content.querySelectorAll('.admin-create select[required]').forEach(control=>{
        const mark=make('span','admin-required',' *');mark.setAttribute('aria-hidden','true');control.before(mark);
    });
    content.querySelectorAll('[aria-describedby]').forEach(control=>{
        const hint=document.getElementById(control.getAttribute('aria-describedby'));
        if(hint)control.title=hint.textContent.trim();
    });

    const createForm=createPanel?.querySelector('form');
    const flightSelect=createForm?.querySelector('select[name=flight_id]');
    if(flightSelect){
        const query=new URLSearchParams(location.search).get('q');
        const match=query&&[...flightSelect.options].find(option=>option.value&&option.textContent.trim().toLowerCase()===query.toLowerCase());
        if(match){flightSelect.value=match.value;flightSelect.dispatchEvent(new Event('change',{bubbles:true}));}
    }
    if(createForm){
        const validate = () => {
            const departure=createForm.elements.namedItem('departure_airport_id');
            const arrival=createForm.elements.namedItem('arrival_airport_id');
            if(arrival)arrival.setCustomValidity(departure.value&&arrival.value===departure.value?'Choose a different arrival airport. A flight cannot depart and arrive at the same airport.':'');
            const departs=createForm.elements.namedItem('departure_at');
            const arrives=createForm.elements.namedItem('arrival_at');
            if(arrives)arrives.setCustomValidity(departs.value&&arrives.value&&arrives.value<=departs.value?'Arrival must be later than departure. Check the date as well as the time.':'');
            const rows=createForm.elements.namedItem('rows');
            if(rows)rows.setCustomValidity(createForm.querySelector('input[name="letters[]"]:checked')?'':'Choose at least one seat letter.');
        };
        createForm.addEventListener('input',validate);createForm.addEventListener('change',validate);
        createForm.addEventListener('reset',()=>setTimeout(validate,0));validate();
    }

    content.querySelectorAll('form.inline-form').forEach(form=>{
        const select=form.querySelector('select[name=status]');if(!select)return;
        const row=form.closest('tr');
        const identity=row?.querySelector('strong')?.textContent || 'this record';
        select.setAttribute('aria-label',`New status for ${identity}`);
        const hint=make('small','admin-status-help');
        const kind=form.elements.namedItem('kind')?.value;
        hint.textContent=kind==='seat_status'?'Updates only seats without a reservation.':'To confirm a pending booking, review and verify its payment first.';
        form.after(hint);
    });

    const rememberSubmission = form => {
        if(!form.matches('.admin-create form,.admin-record-form'))return;
        // Tokens, hidden fields, payment/customer details and passwords are never stored.
        const values=[...form.elements].filter(control=>control.name&&control.matches('input:not([type=hidden]):not([type=password]),select'))
            .map(control=>({name:control.name,value:control.value,checked:control.checked}));
        try{sessionStorage.setItem(draftKey,JSON.stringify({time:Date.now(),formId:form.getAttribute('id') || '',kind:form.elements.namedItem('kind')?.value,values}));}catch{/* POST still works without storage. */}
    };
    content.addEventListener('formdata',event=>{
        // The shared submit handler sets this flag only after any confirmation is accepted.
        if(event.target.dataset.submitting==='true')rememberSubmission(event.target);
    });
    // Supply context to the shared confirmation dialog before its submit listener runs.
    document.addEventListener('submit',event=>{
        const form=event.target;if(!form.matches('.admin-content form'))return;
        const kind=form.elements.namedItem('kind')?.value;
        const status=form.elements.namedItem('status')?.value;
        const row=form.closest('tr');
        const identity=row?.querySelector('strong')?.textContent || [...form.elements].find(control=>['name','flight_number'].includes(control.name))?.value || 'this record';
        let message='';
        if(kind==='booking_status'&&['cancelled','expired'].includes(status))message=`Set booking ${identity} to ${status}? Its reserved seats will be released and issued tickets will be voided.`;
        else if(kind==='booking_status')message=`Change booking ${identity} to ${friendly(status)}? The booking must meet the requirements for this status.`;
        else if(kind==='seat_status')message=`Set all unreserved seats on ${identity} to ${friendly(status)}? Existing reservations will stay unchanged.`;
        else if(['airlines','airports'].includes(kind)&&status==='inactive')message=`Make ${identity} inactive? It will no longer appear in the new-flight selector.`;
        else if(kind==='flights'&&status==='cancelled')message=`Set flight ${identity} to Cancelled? Check its existing bookings and payments separately; this action changes the flight status only.`;
        if(message){form.dataset.confirm=message;form.dataset.confirmTitle='Confirm this change';form.dataset.confirmButton='Apply change';}
        // Fallback for browsers without a formdata event.
        setTimeout(()=>{if(!event.defaultPrevented)rememberSubmission(form);},0);
    },true);
    content.addEventListener('input',event=>{
        const form=event.target.form;
        if(form){delete form.dataset.confirmed;delete form.dataset.confirm;}
    });

    // A server error stays visible and keyboard users land on its explanation.
    const error=content.querySelector('[data-admin-feedback].error');
    if(error){
        try{
            const draft=JSON.parse(sessionStorage.getItem(draftKey)||'null');
            if(draft&&Date.now()-draft.time<600000&&Array.isArray(draft.values)&&draft.values.length<50){
                const form=draft.formId?document.getElementById(draft.formId):createForm;
                if(form?.elements.namedItem('kind')?.value===draft.kind){
                    if(form===createForm)createPanel.open=true;
                    else{
                        const row=form.closest('tr');
                        if(row?.hidden){
                            const search=row.closest('[data-admin-table]')?.parentElement.querySelector('.admin-search-label input');
                            if(search){search.value=row.querySelector('strong')?.textContent || form.elements.namedItem('name')?.value || '';search.dispatchEvent(new Event('input',{bubbles:true}));}
                        }
                        form.querySelector('.admin-edit-button')?.click();
                    }
                    draft.values.forEach(item=>{
                        [...form.elements].filter(control=>control.name===item.name&&control.matches('input:not([type=hidden]):not([type=password]),select')).forEach(control=>{
                            if(control.type==='checkbox')control.checked=Boolean(item.checked);else if(typeof item.value==='string')control.value=item.value;
                            control.dispatchEvent(new Event('input',{bubbles:true}));
                        });
                    });
                    announce('Your last submitted details have been restored. Review the error above, correct the details, and save again.');
                }
            }
        }catch{/* Error messages remain usable when browser storage is unavailable. */}
        error.tabIndex=-1;
        setTimeout(()=>{error.hidden=false;error.focus();},0);
    }else if(content.querySelector('[data-admin-feedback].success')){
        try{
            const draft=JSON.parse(sessionStorage.getItem(draftKey)||'null');
            const flightNumber=draft?.values?.find(item=>item.name==='flight_number')?.value;
            if(draft?.kind==='flights'&&!draft.formId&&flightNumber&&Date.now()-draft.time<600000){
                const next=content.querySelector('.admin-next-step a');
                if(next){const url=new URL('/admin/seats',location.origin);url.searchParams.set('q',flightNumber);url.hash='admin-create';next.href=url.href;next.textContent=`Create seats for ${flightNumber}`;}
            }
            sessionStorage.removeItem(draftKey);
        }catch{/* Optional recovery state. */}
    }
})();
