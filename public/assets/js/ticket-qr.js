(() => {
    const ns = 'http://www.w3.org/2000/svg';
    document.querySelectorAll('[data-ticket-qr]').forEach(container => {
        try {
            const code = qrcodegen.QrCode.encodeText(container.dataset.qrPayload, qrcodegen.QrCode.Ecc.MEDIUM);
            const border = 4;
            const svg = document.createElementNS(ns, 'svg');
            svg.setAttribute('viewBox', `0 0 ${code.size + border * 2} ${code.size + border * 2}`);
            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-label', 'QR code containing this ticket’s booking references');
            svg.setAttribute('shape-rendering', 'crispEdges');
            const background = document.createElementNS(ns, 'rect');
            background.setAttribute('width', '100%');
            background.setAttribute('height', '100%');
            background.setAttribute('fill', '#fff');
            const path = document.createElementNS(ns, 'path');
            const modules = [];
            for (let y = 0; y < code.size; y++) {
                for (let x = 0; x < code.size; x++) {
                    if (code.getModule(x, y)) modules.push(`M${x + border},${y + border}h1v1h-1z`);
                }
            }
            path.setAttribute('d', modules.join(''));
            path.setAttribute('fill', '#000');
            svg.append(background, path);
            container.replaceChildren(svg);
        } catch {
            container.textContent = 'See ticket references';
        }
    });
})();
