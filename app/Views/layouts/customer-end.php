        </div>
    </div>
</div>
<script>
const customerToggle=document.querySelector('.customer-menu-toggle');
const customerSidebar=document.getElementById('customer-sidebar');
const customerBackdrop=document.querySelector('.customer-backdrop');
const customerClose=document.querySelector('.customer-sidebar-close');
const customerSetOpen=open=>{document.body.classList.toggle('customer-nav-open',open);customerToggle?.setAttribute('aria-expanded',String(open));customerToggle?.setAttribute('aria-label',open?'Close customer navigation':'Open customer navigation');document.body.style.overflow=open?'hidden':'';if(open)customerClose?.focus()};
customerToggle?.addEventListener('click',()=>customerSetOpen(!document.body.classList.contains('customer-nav-open')));
customerClose?.addEventListener('click',()=>customerSetOpen(false));
customerBackdrop?.addEventListener('click',()=>customerSetOpen(false));
customerSidebar?.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>customerSetOpen(false)));
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.body.classList.contains('customer-nav-open')){customerSetOpen(false);customerToggle?.focus()}});
</script>
<?php require BASE_PATH.'/app/Views/layouts/footer.php'; ?>
