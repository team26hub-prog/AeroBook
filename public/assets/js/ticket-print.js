(() => {
    let activeTicket = null;
    let originalTitle = '';
    const reset = () => {
        if (!activeTicket) return;
        activeTicket.classList.remove('ticket-print-target');
        document.body.classList.remove('ticket-print-active');
        document.title = originalTitle;
        activeTicket = null;
    };
    window.addEventListener('afterprint', reset);
    document.querySelectorAll('[data-print-ticket]').forEach(button => {
        button.addEventListener('click', () => {
            const ticket = button.closest('.e-ticket');
            if (!ticket || activeTicket) return;
            originalTitle = document.title;
            activeTicket = ticket;
            ticket.classList.add('ticket-print-target');
            document.body.classList.add('ticket-print-active');
            document.title = `AeroBook-ticket-${ticket.dataset.ticketNumber}`;
            try {
                window.print();
            } catch (error) {
                reset();
                throw error;
            }
        });
    });
})();
