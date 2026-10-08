(() => {
    const el = id => document.getElementById(id);
    const isGuest = window.libraryAccess?.roleKey === 'guest';
    const isStaff = ['admin','manager','employee'].includes(window.libraryAccess?.roleKey);
    const isCustomer = window.libraryAccess?.roleKey === 'customer';

    function localToday() {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function loanStatus(loan) {
        if ((loan.TrangThai || '') === 'Chờ duyệt') return ['Chờ duyệt', 'status-warning'];
        if (loan.NgayTra || (loan.TrangThai || '') === 'Đã trả') return ['Đã trả', 'status-success'];
        if (loan.HanTra && loan.HanTra < localToday()) return ['Quá hạn', 'status-danger'];
        return ['Đang mượn', 'status-success'];
    }

    function renderRows(body, rows, emptyHtml) {
        if (!body) return;
        body.innerHTML = rows.length ? rows.join('') : emptyHtml;
    }

    function bindDashboardNavigation() {
        document.querySelectorAll('[data-view-all]').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();
                loadPage(link.dataset.viewAll, link.dataset.title || '');
            });
        });

        document.querySelectorAll('[data-dashboard-target]').forEach(card => {
            card.style.cursor = 'pointer';
            card.setAttribute('tabindex', '0');
            const open = () => {
                const target = card.dataset.dashboardTarget;
                const titles = { 'sach.php': 'Sách', 'docgia.php': 'Độc giả', 'muontra.php': 'Mượn - Trả', 'quahan.php': 'Quá hạn' };
                loadPage(target, titles[target] || '');
            };
            card.addEventListener('click', open);
            card.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); }
            });
        });
    }

    function normalizeGuestText(value) {
        return String(value || '').trim();
    }

    function splitGuestLines(text, maxChars = 16, maxLines = 4) {
        const words = normalizeGuestText(text).split(/\s+/).filter(Boolean);
        if (!words.length) return ['Sách'];
        const lines = [];
        let current = '';
        for (const word of words) {
            const trial = current ? `${current} ${word}` : word;
            if (trial.length <= maxChars || !current) current = trial;
            else {
                lines.push(current);
                current = word;
                if (lines.length >= maxLines - 1) break;
            }
        }
        const rest = words.slice(lines.join(' ').split(/\s+/).filter(Boolean).length);
        if (current) lines.push(current);
        if (rest.length) {
            const tail = [lines.pop() || '', ...rest].join(' ').trim();
            lines.push((tail.length > maxChars ? tail.slice(0, Math.max(6, maxChars - 1)).trim() + '…' : tail));
        }
        return lines.slice(0, maxLines);
    }

    function escapeSvgText(value) {
        return String(value || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' }[m]));
    }

    function genreTheme(name = '') {
        const value = normalizeGuestText(name).toLowerCase();
        const themes = [
            { keys: ['ngôn tình', 'tình', 'romance'], icon: '❤', fa: 'fa-heart', palette: ['#ec4899', '#8b5cf6', '#2563eb'], label: 'Ngôn tình' },
            { keys: ['công nghệ', 'tech', 'lập trình'], icon: '⌘', fa: 'fa-microchip', palette: ['#2563eb', '#0891b2', '#14b8a6'], label: 'Công nghệ' },
            { keys: ['khoa học', 'vũ trụ', 'thiên văn'], icon: '✦', fa: 'fa-flask', palette: ['#4f46e5', '#2563eb', '#0ea5e9'], label: 'Khoa học' },
            { keys: ['kinh dị', 'bí ẩn', 'ma'], icon: '☾', fa: 'fa-ghost', palette: ['#312e81', '#1d4ed8', '#0891b2'], label: 'Kinh dị' },
            { keys: ['ngoại ngữ', 'tiếng anh', 'language'], icon: 'A', fa: 'fa-book-open-reader', palette: ['#0f766e', '#0891b2', '#2563eb'], label: 'Ngoại ngữ' },
            { keys: ['nấu ăn', 'ẩm thực', 'món'], icon: '✿', fa: 'fa-utensils', palette: ['#ea580c', '#f59e0b', '#fb7185'], label: 'Nấu ăn' },
            { keys: ['phiêu lưu', 'thám hiểm'], icon: '✧', fa: 'fa-compass', palette: ['#1d4ed8', '#4f46e5', '#06b6d4'], label: 'Phiêu lưu' },
            { keys: ['y học', 'sức khỏe', 'dinh dưỡng'], icon: '✚', fa: 'fa-staff-snake', palette: ['#0ea5e9', '#2563eb', '#14b8a6'], label: 'Y học' },
            { keys: ['thể thao'], icon: '⚑', fa: 'fa-person-running', palette: ['#2563eb', '#0284c7', '#22c55e'], label: 'Thể thao' },
            { keys: ['làm giàu', 'kinh doanh', 'tài chính'], icon: '$', fa: 'fa-chart-line', palette: ['#2563eb', '#7c3aed', '#ec4899'], label: 'Kinh doanh' },
            { keys: ['thiếu nhi', 'cổ tích', 'trẻ em'], icon: '★', fa: 'fa-wand-sparkles', palette: ['#0ea5e9', '#6366f1', '#ec4899'], label: 'Thiếu nhi' }
        ];
        return themes.find(theme => theme.keys.some(key => value.includes(key))) || { icon: '✦', fa: 'fa-book-open', palette: ['#2563eb', '#4338ca', '#0891b2'], label: normalizeGuestText(name) || 'Sách' };
    }

    function svgDataUri(svg) {
        return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
    }

    function generatedCoverSrc(book, variant = 'featured') {
        const title = normalizeGuestText(book?.TenSach || 'Sách hay');
        const author = normalizeGuestText(book?.TacGia || 'Thư viện');
        const genre = normalizeGuestText(book?.TenTheLoai || 'Thể loại');
        const theme = genreTheme(`${genre} ${title}`);
        const dims = {
            featured: { w: 520, h: 680, titleSize: 44, subSize: 20, maxChars: 14, maxLines: 4 },
            heroLarge: { w: 560, h: 760, titleSize: 48, subSize: 22, maxChars: 14, maxLines: 4 },
            heroSmall: { w: 430, h: 580, titleSize: 34, subSize: 18, maxChars: 12, maxLines: 4 },
            marquee: { w: 300, h: 430, titleSize: 26, subSize: 16, maxChars: 12, maxLines: 4 },
            thumb: { w: 220, h: 300, titleSize: 22, subSize: 14, maxChars: 11, maxLines: 4 },
            category: { w: 300, h: 220, titleSize: 24, subSize: 14, maxChars: 12, maxLines: 2 }
        }[variant] || { w: 520, h: 680, titleSize: 44, subSize: 20, maxChars: 14, maxLines: 4 };
        const lines = splitGuestLines(title, dims.maxChars, dims.maxLines);
        const titleSvg = lines.map((line, index) => `<text x="48" y="${250 + index * (dims.titleSize * 1.08)}" font-size="${dims.titleSize}" font-weight="800" fill="#ffffff" font-family="Inter,Arial,sans-serif">${escapeSvgText(line)}</text>`).join('');
        const badgeLabel = escapeSvgText(theme.label || genre || 'Sách');
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${dims.w}" height="${dims.h}" viewBox="0 0 ${dims.w} ${dims.h}">
            <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="${theme.palette[0]}"/>
                    <stop offset="52%" stop-color="${theme.palette[1]}"/>
                    <stop offset="100%" stop-color="${theme.palette[2]}"/>
                </linearGradient>
                <linearGradient id="shine" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="rgba(255,255,255,.28)"/>
                    <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                </linearGradient>
                <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="18" stdDeviation="24" flood-color="rgba(15,23,42,.28)"/></filter>
            </defs>
            <rect x="0" y="0" width="${dims.w}" height="${dims.h}" rx="34" fill="url(#bg)"/>
            <rect x="0" y="0" width="${dims.w}" height="${dims.h}" rx="34" fill="url(#shine)" opacity=".88"/>
            <circle cx="${dims.w * .86}" cy="${dims.h * .18}" r="${Math.round(dims.w * .18)}" fill="rgba(255,255,255,.10)"/>
            <circle cx="${dims.w * .16}" cy="${dims.h * .88}" r="${Math.round(dims.w * .22)}" fill="rgba(255,255,255,.10)"/>
            <path d="M${dims.w * .58} ${dims.h * .12}c${dims.w * .18} ${-dims.h * .03} ${dims.w * .27} ${dims.h * .09} ${dims.w * .18} ${dims.h * .18}s-${dims.w * .28} ${dims.h * .11}-${dims.w * .34} ${0} ${dims.w * .04}-${dims.h * .16} ${dims.w * .16}-${dims.h * .18}Z" fill="rgba(255,255,255,.12)"/>
            <rect x="36" y="40" rx="999" ry="999" width="${Math.min(dims.w - 72, 190)}" height="42" fill="rgba(15,23,42,.22)"/>
            <text x="57" y="68" font-size="16" font-weight="700" letter-spacing="1.4" fill="#ffffff" font-family="Inter,Arial,sans-serif">${badgeLabel}</text>
            <text x="${dims.w - 52}" y="88" text-anchor="end" font-size="64" font-weight="700" fill="rgba(255,255,255,.18)" font-family="Inter,Arial,sans-serif">${escapeSvgText(theme.icon)}</text>
            <rect x="36" y="${dims.h - 154}" width="${dims.w - 72}" height="1" fill="rgba(255,255,255,.22)"/>
            ${titleSvg}
            <text x="48" y="${dims.h - 102}" font-size="${dims.subSize}" font-weight="600" fill="rgba(255,255,255,.9)" font-family="Inter,Arial,sans-serif">${escapeSvgText(author)}</text>
            <text x="48" y="${dims.h - 66}" font-size="${Math.max(12, dims.subSize - 4)}" font-weight="500" fill="rgba(255,255,255,.72)" font-family="Inter,Arial,sans-serif">${escapeSvgText(genre)}</text>
            <rect x="20" y="20" width="${dims.w - 40}" height="${dims.h - 40}" rx="28" fill="none" stroke="rgba(255,255,255,.18)"/>
        </svg>`;
        return svgDataUri(svg);
    }

    function categoryArtSrc(categoryName = '') {
        const theme = genreTheme(categoryName);
        const label = normalizeGuestText(categoryName || theme.label || 'Thể loại');
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="180" viewBox="0 0 240 180">
            <defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="${theme.palette[0]}"/><stop offset="50%" stop-color="${theme.palette[1]}"/><stop offset="100%" stop-color="${theme.palette[2]}"/></linearGradient></defs>
            <rect width="240" height="180" rx="28" fill="url(#bg)"/>
            <circle cx="192" cy="42" r="34" fill="rgba(255,255,255,.16)"/>
            <circle cx="44" cy="136" r="52" fill="rgba(255,255,255,.10)"/>
            <text x="34" y="82" font-size="48" font-weight="700" fill="#ffffff" font-family="Inter,Arial,sans-serif">${escapeSvgText(theme.icon)}</text>
            <text x="34" y="124" font-size="24" font-weight="800" fill="#ffffff" font-family="Inter,Arial,sans-serif">${escapeSvgText(label.length > 14 ? label.slice(0, 14) + '…' : label)}</text>
        </svg>`;
        return svgDataUri(svg);
    }

    function getBookCoverSource(book, variant = 'featured') {
        const raw = normalizeGuestText(book?.HinhAnh || '');
        return raw || generatedCoverSrc(book, variant);
    }

    function guestCoverMarkup(book, extraClass = 'guest-book-cover', variant = 'featured') {
        const src = getBookCoverSource(book, variant);
        const fallback = generatedCoverSrc({ ...book, HinhAnh: '' }, variant);
        return `<img class="${extraClass}" src="${escapeHTML(src)}" data-fallback="${escapeHTML(fallback)}" alt="Bìa ${escapeHTML(book?.TenSach || 'sách')}" loading="lazy" onerror="if(this.dataset.fallback && this.src !== this.dataset.fallback){ this.src = this.dataset.fallback; this.dataset.fallback=''; } else { this.outerHTML = '<div class=&quot;guest-book-fallback&quot;>${escapeHTML(book?.TenSach || 'Sách')}</div>'; }">`;
    }

    function animateGuestCount(node) {
        if (!node || node.dataset.countAnimated === '1') return;
        const raw = Number(String(node.textContent || '0').replace(/[^0-9.-]/g, '')) || 0;
        node.dataset.countAnimated = '1';
        const duration = 900;
        const start = performance.now();
        const ease = t => 1 - Math.pow(1 - t, 3);
        node.textContent = '0';
        const tick = now => {
            const p = Math.min(1, (now - start) / duration);
            node.textContent = Math.round(raw * ease(p)).toLocaleString('vi-VN');
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }

    function bindGuestRevealAndShell() {
        document.body.classList.add('guest-motion-active');
        const header = document.querySelector('.dashboard-header');
        const syncHeader = () => header?.classList.toggle('guest-header-scrolled', window.scrollY > 24);
        syncHeader();
        window.addEventListener('scroll', syncHeader, { passive: true });

        const reveal = document.querySelectorAll('.motion-reveal');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
            }), { threshold: .08, rootMargin: '0px 0px -30px 0px' });
            reveal.forEach(node => observer.observe(node));
        } else reveal.forEach(node => node.classList.add('is-visible'));
    }

    function bindGuestBookTilt(scope) {
        if (window.matchMedia('(hover: none)').matches) return;
        scope.querySelectorAll('.guest-book-card').forEach(card => {
            card.addEventListener('mousemove', event => {
                const r = card.getBoundingClientRect();
                const x = (event.clientX - r.left) / r.width - .5;
                const y = (event.clientY - r.top) / r.height - .5;
                card.style.transform = `perspective(800px) rotateX(${(-y * 5).toFixed(2)}deg) rotateY(${(x * 7).toFixed(2)}deg) translateY(-6px)`;
            });
            card.addEventListener('mouseleave', () => { card.style.transform = ''; });
        });
    }

    async function initGuestMotion() {
        bindGuestRevealAndShell();
        ['totalBooks','totalTitles','totalCategories','availableTitles'].forEach(id => animateGuestCount(el(id)));

        const searchInput = el('guestHeroSearch');
        const runSearch = () => {
            const q = searchInput?.value.trim() || '';
            if (q) sessionStorage.setItem('guestBookSearch', q); else sessionStorage.removeItem('guestBookSearch');
            sessionStorage.removeItem('guestBookCategory');
            loadPage('sach.php', 'Sách');
        };
        el('guestHeroSearchBtn')?.addEventListener('click', runSearch);
        searchInput?.addEventListener('keydown', e => { if (e.key === 'Enter') runSearch(); });

        try {
            const payload = await libraryApi.get('books');
            const books = payload.data?.books || [];
            const categories = payload.data?.categories || [];

            const catTrack = el('guestCategoryTrack');
            if (catTrack) {
                const base = categories.length ? categories : [{MaTheLoai:'',TenTheLoai:'Tất cả sách'}];
                const renderCats = [...base, ...base].map((c,i) => {
                    const theme = genreTheme(c.TenTheLoai || 'Thể loại');
                    return `<button type="button" class="guest-category-chip" data-guest-category="${escapeHTML(c.MaTheLoai || '')}" style="--chip-c1:${theme.palette[0]};--chip-c2:${theme.palette[1]};--chip-c3:${theme.palette[2]}"><span class="guest-category-art"><img src="${escapeHTML(categoryArtSrc(c.TenTheLoai || 'Thể loại'))}" alt="${escapeHTML(c.TenTheLoai || 'Thể loại')}" loading="lazy"></span><span class="guest-category-copy"><strong>${escapeHTML(c.TenTheLoai || 'Thể loại')}</strong><small>Khám phá</small></span></button>`;
                }).join('');
                catTrack.innerHTML = renderCats;
                catTrack.querySelectorAll('[data-guest-category]').forEach(btn => btn.addEventListener('click', () => {
                    const id = btn.dataset.guestCategory || '';
                    if (id) sessionStorage.setItem('guestBookCategory', id); else sessionStorage.removeItem('guestBookCategory');
                    sessionStorage.removeItem('guestBookSearch');
                    loadPage('sach.php','Sách');
                }));
            }

            const featured = books.filter(b => Number(b.SoLuong || 0) > 0).slice(0, 12);
            const featuredTrack = el('guestFeaturedTrack');
            if (featuredTrack) {
                featuredTrack.innerHTML = featured.map((book, idx) => `<article class="guest-book-card" tabindex="0">
                    <div class="guest-book-cover-wrap">${guestCoverMarkup(book, 'guest-book-cover', 'featured')}<span class="guest-book-badge">${idx < 3 ? 'NỔI BẬT' : escapeHTML(book.TenTheLoai || 'SÁCH')}</span></div>
                    <div class="guest-book-info"><h3 title="${escapeHTML(book.TenSach || '')}">${escapeHTML(book.TenSach || '--')}</h3><p>${escapeHTML(book.TacGia || 'Chưa rõ tác giả')}</p><div class="guest-book-meta"><span>${escapeHTML(book.TenTheLoai || 'Chưa phân loại')}</span><span>${Number(book.SoLuong || 0)} cuốn</span></div></div>
                    <button type="button" class="guest-book-action" data-featured-book="${escapeHTML(book.MaSach || '')}">Xem sách <i class="fa-solid fa-arrow-right"></i></button>
                </article>`).join('') || '<div class="adv-row">Chưa có sách để hiển thị.</div>';
                featuredTrack.querySelectorAll('[data-featured-book]').forEach(btn => btn.addEventListener('click', () => {
                    const card = btn.closest('.guest-book-card');
                    const title = card?.querySelector('h3')?.textContent || '';
                    if (title) sessionStorage.setItem('guestBookSearch', title);
                    loadPage('sach.php','Sách');
                }));
                bindGuestBookTilt(featuredTrack);
            }

            const heroBooks = (featured.length ? featured : books).slice(0, 3);
            const heroCluster = el('heroCoverCluster');
            if (heroCluster) {
                const variants = ['heroLarge', 'heroSmall', 'heroSmall'];
                const cls = ['hc-main', 'hc-side hc-side-top', 'hc-side hc-side-bottom'];
                heroCluster.innerHTML = heroBooks.map((book, index) => `<div class="hero-cover-card ${cls[index] || 'hc-side'}"><img src="${escapeHTML(getBookCoverSource(book, variants[index] || 'heroSmall'))}" alt="${escapeHTML(book.TenSach || 'Sách')}" loading="lazy"><div class="hero-cover-meta"><span>${escapeHTML(book.TenTheLoai || 'Sách')}</span><strong>${escapeHTML(book.TenSach || '--')}</strong></div></div>`).join('');
            }
            const heroStats = el('heroVisualStats');
            if (heroStats) {
                const topCategory = categories[0]?.TenTheLoai || 'Thể loại';
                heroStats.innerHTML = [`<div class="hero-visual-stat"><strong>${books.length || 0}</strong><span>Đầu sách hiển thị</span></div>`,`<div class="hero-visual-stat"><strong>${categories.length || 0}</strong><span>Thể loại</span></div>`,`<div class="hero-visual-stat"><strong>${escapeHTML(topCategory)}</strong><span>Nổi bật</span></div>`].join('');
            }
            const heroGenreCloud = el('heroGenreCloud');
            if (heroGenreCloud) {
                heroGenreCloud.innerHTML = categories.slice(0, 5).map(cat => {
                    const theme = genreTheme(cat.TenTheLoai || 'Thể loại');
                    return `<span class="hero-genre-pill" style="--pill-c1:${theme.palette[0]};--pill-c2:${theme.palette[2]}"><i class="fa-solid ${theme.fa || 'fa-book-open'}"></i>${escapeHTML(cat.TenTheLoai || 'Thể loại')}</span>`;
                }).join('') || '<span class="hero-genre-pill"><i class="fa-solid fa-book-open"></i>Thư viện</span>';
            }

            const makeMarquee = arr => {
                if (!arr.length) return '<div class="guest-marquee-item"><div class="guest-marquee-fallback">Thư viện</div></div>';
                const doubled = [...arr, ...arr];
                return doubled.map(book => `<div class="guest-marquee-item" title="${escapeHTML(book.TenSach || '')}"><img src="${escapeHTML(getBookCoverSource(book, 'marquee'))}" alt="${escapeHTML(book.TenSach || 'Bìa sách')}" loading="lazy" data-fallback="${escapeHTML(generatedCoverSrc({ ...book, HinhAnh: '' }, 'marquee'))}" onerror="if(this.dataset.fallback && this.src !== this.dataset.fallback){ this.src = this.dataset.fallback; this.dataset.fallback=''; } else { this.outerHTML='<div class=&quot;guest-marquee-fallback&quot;>${escapeHTML(book.TenSach || 'Sách')}</div>'; }"></div>`).join('');
            };
            const first = books.slice(0, 10), second = books.slice(10, 20).length ? books.slice(10,20) : [...books].reverse().slice(0,10);
            if (el('guestMarqueeOne')) el('guestMarqueeOne').innerHTML = makeMarquee(first);
            if (el('guestMarqueeTwo')) el('guestMarqueeTwo').innerHTML = makeMarquee(second);

            const viewport = el('guestFeaturedViewport');
            if (viewport && featured.length) {
                let timer = null;
                const step = () => Math.min(238, Math.max(190, viewport.clientWidth * .24));
                const next = direction => {
                    const max = viewport.scrollWidth - viewport.clientWidth;
                    if (direction > 0 && viewport.scrollLeft >= max - 8) viewport.scrollTo({left:0,behavior:'smooth'});
                    else if (direction < 0 && viewport.scrollLeft <= 8) viewport.scrollTo({left:max,behavior:'smooth'});
                    else viewport.scrollBy({left:step()*direction,behavior:'smooth'});
                };
                el('guestFeaturedNext')?.addEventListener('click', () => next(1));
                el('guestFeaturedPrev')?.addEventListener('click', () => next(-1));
                const startAuto = () => { if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; clearInterval(timer); timer = setInterval(() => next(1), 3200); };
                const stopAuto = () => clearInterval(timer);
                viewport.addEventListener('mouseenter', stopAuto); viewport.addEventListener('mouseleave', startAuto);
                viewport.addEventListener('pointerdown', stopAuto); viewport.addEventListener('pointerup', startAuto);
                startAuto();
            }
        } catch (error) {
            console.warn('Không thể tải khu chuyển động trang chủ:', error);
        }
    }


    function readerBookCard(book, options = {}) {
        const title = escapeHTML(book?.TenSach || '--');
        const author = escapeHTML(book?.TacGia || 'Chưa rõ tác giả');
        const genre = escapeHTML(book?.TenTheLoai || 'Sách');
        const src = escapeHTML(getBookCoverSource(book, options.variant || 'featured'));
        const value = options.value != null ? `<span class="reader-book-value">${escapeHTML(options.value)}</span>` : '';
        const heart = options.heart ? `<button type="button" class="reader-heart ${options.active ? 'active' : ''}" data-home-fav="${escapeHTML(book?.MaSach || '')}" title="Thêm/bỏ muốn đọc"><i class="fa-${options.active ? 'solid' : 'regular'} fa-heart"></i></button>` : '';
        return `<article class="reader-book-card" data-reader-book="${escapeHTML(book?.MaSach || '')}" tabindex="0">
            <div class="reader-cover-wrap"><img src="${src}" alt="Bìa ${title}" loading="lazy"><span class="reader-genre-tag">${genre}</span>${heart}</div>
            <div class="reader-book-copy"><h3>${title}</h3><p>${author}</p><div class="reader-book-foot">${value}<button type="button" class="reader-book-open">Xem sách <i class="fa-solid fa-arrow-right"></i></button></div></div>
        </article>`;
    }

    function openReaderBookByTitle(card) {
        const title = card?.querySelector('h3')?.textContent?.trim() || '';
        if (title) sessionStorage.setItem('guestBookSearch', title);
        sessionStorage.removeItem('guestBookCategory');
        loadPage('sach.php','Sách');
    }

    function bindReaderBookCards(scope = document) {
        scope.querySelectorAll('.reader-book-card').forEach(card => {
            const open = e => {
                if (e?.target?.closest('[data-home-fav]')) return;
                openReaderBookByTitle(card);
            };
            card.querySelector('.reader-book-open')?.addEventListener('click', open);
            card.addEventListener('dblclick', open);
            card.addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && !e.target.closest('[data-home-fav]')) { e.preventDefault(); open(e); } });
        });
    }

    function readerLoanCard(loan) {
        const today = new Date(); today.setHours(0,0,0,0);
        const due = loan.HanTra ? new Date(`${loan.HanTra}T00:00:00`) : null;
        const borrow = loan.NgayMuon ? new Date(`${loan.NgayMuon}T00:00:00`) : null;
        const totalDays = due && borrow ? Math.max(1, Math.round((due - borrow) / 86400000)) : 1;
        const leftDays = due ? Math.ceil((due - today) / 86400000) : 0;
        const elapsed = due && borrow ? Math.max(0, Math.round((today - borrow) / 86400000)) : 0;
        const pct = Math.max(4, Math.min(100, Math.round(elapsed / totalDays * 100)));
        const overdue = due && due < today;
        const status = overdue ? `Quá hạn ${Math.abs(leftDays)} ngày` : leftDays === 0 ? 'Đến hạn hôm nay' : `Còn ${leftDays} ngày`;
        const tone = overdue ? 'danger' : leftDays <= 2 ? 'warning' : 'safe';
        const firstTitle = String(loan.TenSach || 'Sách đang mượn').split(',')[0].trim();
        return `<article class="reader-loan-card tone-${tone}">
            <div class="reader-loan-cover"><i class="fa-solid fa-book-open"></i></div>
            <div class="reader-loan-copy"><span class="reader-loan-id">PM${String(loan.MaPhieuMuon || '').padStart(3,'0')}</span><h3>${escapeHTML(firstTitle)}</h3><p>${Number(loan.TongSoLuong || 0).toLocaleString('vi-VN')} cuốn • Hạn trả ${formatLibraryDate(loan.HanTra)}</p><div class="reader-loan-progress"><i style="width:${pct}%"></i></div><div class="reader-loan-status"><span>${status}</span><button type="button" onclick="loadPage('muontra.php','Mượn sách')">Xem phiếu</button></div></div>
        </article>`;
    }

    function animateReaderCounts() {
        document.querySelectorAll('.reader-metric strong').forEach(node => {
            const target = Number(String(node.textContent || '0').replace(/[^0-9.-]/g,'')) || 0;
            const start = performance.now(); const duration = 800;
            const tick = now => { const p=Math.min(1,(now-start)/duration); node.textContent=Math.round(target*(1-Math.pow(1-p,3))).toLocaleString('vi-VN'); if(p<1)requestAnimationFrame(tick); };
            node.textContent='0'; requestAnimationFrame(tick);
        });
    }

    window.readerHomeBookCard = readerBookCard;
    window.bindReaderHomeBookCards = bindReaderBookCards;
    window.readerHomeLoanCard = readerLoanCard;

    function bindReaderReveal() {
        document.body.classList.add('reader-home-active');
        const nodes = document.querySelectorAll('.reader-reveal');
        if (!('IntersectionObserver' in window)) { nodes.forEach(n=>n.classList.add('is-visible')); return; }
        const io = new IntersectionObserver(entries => entries.forEach(entry => { if(entry.isIntersecting){ entry.target.classList.add('is-visible'); io.unobserve(entry.target); } }), {threshold:.08,rootMargin:'0px 0px -35px 0px'});
        nodes.forEach(n=>io.observe(n));
    }

    async function init() {
        bindDashboardNavigation();
        if (isStaff) {
            document.body.classList.add('staff-motion-active');
            const header = document.querySelector('.dashboard-header');
            const syncStaffHeader = () => header?.classList.toggle('staff-header-scrolled', window.scrollY > 18);
            syncStaffHeader();
            window.addEventListener('scroll', syncStaffHeader, { passive: true });
        } else {
            document.body.classList.remove('staff-motion-active');
        }
        try {
            const payload = await libraryApi.get('dashboard');
            const data = payload.data || {};
            const summary = data.summary || {};

            if (el('totalBooks')) el('totalBooks').textContent = Number(summary.totalBooks || 0).toLocaleString('vi-VN');
            if (isGuest) {
                if (el('totalTitles')) el('totalTitles').textContent = Number(summary.totalTitles || 0).toLocaleString('vi-VN');
                if (el('totalCategories')) el('totalCategories').textContent = Number(summary.totalCategories || 0).toLocaleString('vi-VN');
                if (el('availableTitles')) el('availableTitles').textContent = Number(summary.availableTitles || 0).toLocaleString('vi-VN');
            } else {
                if (el('totalReaders')) el('totalReaders').textContent = Number(summary.totalReaders || 0).toLocaleString('vi-VN');
                if (el('borrowingBooks')) el('borrowingBooks').textContent = Number(summary.borrowingBooks || 0).toLocaleString('vi-VN');
                if (el('overdueBooks')) el('overdueBooks').textContent = Number(summary.overdueBooks || 0).toLocaleString('vi-VN');
            }

            if (isStaff) {
                const popularSource = (data.popularBooks || []).slice(0, 3);
                const maxBorrow = Math.max(1, ...popularSource.map(book => Number(book.LuotMuon || 0)));
                const popular = popularSource.map((book, index) => `
                    <button type="button" class="staff-book-row dashboard-data-row" data-open-page="sach.php">
                        <span class="staff-book-rank">#${index + 1}</span>
                        <img class="staff-book-cover" src="${escapeHTML(getBookCoverSource(book, 'thumb'))}" alt="${escapeHTML(book.TenSach || 'Sách')}" loading="lazy">
                        <span class="staff-book-copy"><strong>${escapeHTML(book.TenSach || '--')}</strong><small>${escapeHTML(book.TacGia || 'Chưa rõ tác giả')} • ${escapeHTML(book.TenTheLoai || 'Chưa phân loại')}</small><span class="staff-progress"><i style="width:${Math.max(8, Math.round(Number(book.LuotMuon || 0) / maxBorrow * 100))}%"></i></span></span>
                        <span class="staff-book-value"><b>${Number(book.LuotMuon || 0).toLocaleString('vi-VN')}</b><small>lượt mượn</small></span>
                        <span class="staff-row-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    </button>`);
                renderRows(el('popularBooksBody'), popular, '<div class="staff-empty">Chưa có dữ liệu sách.</div>');

                const newBooks = (data.newBooks || []).slice(0, 3).map((book, index) => `
                    <button type="button" class="staff-book-row dashboard-data-row" data-open-page="sach.php">
                        <span class="staff-book-rank new">${String(index + 1).padStart(2,'0')}</span>
                        <img class="staff-book-cover" src="${escapeHTML(getBookCoverSource(book, 'thumb'))}" alt="${escapeHTML(book.TenSach || 'Sách')}" loading="lazy">
                        <span class="staff-book-copy"><strong>${escapeHTML(book.TenSach || '--')}</strong><small>${escapeHTML(book.TacGia || 'Chưa rõ tác giả')} • ${escapeHTML(book.TenTheLoai || 'Chưa phân loại')}</small><span class="staff-progress stock"><i style="width:${Math.min(100, Math.max(8, Number(book.SoLuong || 0) * 2))}%"></i></span></span>
                        <span class="staff-book-value"><b>${Number(book.SoLuong || 0).toLocaleString('vi-VN')}</b><small>cuốn</small></span>
                        <span class="staff-row-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    </button>`);
                renderRows(el('newBooksBody'), newBooks, '<div class="staff-empty">Chưa có sách.</div>');
            } else if (isCustomer) {
                const popular = (data.popularBooks || []).slice(0, 5).map((book,index) => readerBookCard(book,{variant:'thumb',value:`#${index+1} • ${Number(book.LuotMuon||0)} lượt`}));
                renderRows(el('popularBooksBody'), popular, '<div class="reader-empty">Chưa có dữ liệu sách.</div>');
                const newBooks = (data.newBooks || []).slice(0, 5).map(book => readerBookCard(book,{variant:'thumb',value:`${Number(book.SoLuong||0)} cuốn`}));
                renderRows(el('newBooksBody'), newBooks, '<div class="reader-empty">Chưa có sách.</div>');
                bindReaderBookCards(el('popularBooksBody') || document); bindReaderBookCards(el('newBooksBody') || document);
            } else {
                const popular = (data.popularBooks || []).slice(0, 3).map(book => `<tr class="dashboard-data-row" data-open-page="sach.php"><td>${escapeHTML(book.TenSach || '--')}</td><td>${escapeHTML(book.TacGia || '--')}</td><td>${Number(book.LuotMuon || 0).toLocaleString('vi-VN')}</td></tr>`);
                renderRows(el('popularBooksBody'), popular, '<tr><td colspan="3">Chưa có dữ liệu sách.</td></tr>');
                const newBooks = (data.newBooks || []).slice(0, 3).map(book => `<tr class="dashboard-data-row" data-open-page="sach.php"><td>${escapeHTML(book.TenSach || '--')}</td><td>${escapeHTML(book.TacGia || '--')}</td><td>${Number(book.SoLuong || 0).toLocaleString('vi-VN')}</td></tr>`);
                renderRows(el('newBooksBody'), newBooks, '<tr><td colspan="3">Chưa có sách.</td></tr>');
            }

            if (el('recentLoansBody')) {
                const recent = (data.recentLoans || []).slice(0, 3).map(loan => {
                    const [text, cls] = loanStatus(loan);
                    const initials = String(loan.DocGia || '?').split(/\s+/).filter(Boolean).slice(-2).map(x => x[0]).join('').toUpperCase();
                    return isStaff ? `<tr class="dashboard-data-row" data-open-page="muontra.php">
                        <td><b>PM${String(loan.MaPhieuMuon).padStart(3, '0')}</b></td><td><span class="staff-reader"><i>${escapeHTML(initials)}</i><span>${escapeHTML(loan.DocGia || '--')}</span></span></td><td class="staff-loan-books">${escapeHTML(loan.SachMuon || '--')}</td><td>${formatLibraryDate(loan.NgayMuon)}</td><td><span class="${cls}">${text}</span></td><td><button type="button" class="staff-view-loan" title="Xem phiếu"><i class="fa-solid fa-eye"></i></button></td>
                    </tr>` : isCustomer ? `<button type="button" class="reader-timeline-item dashboard-data-row" data-open-page="muontra.php"><span class="reader-time-dot ${cls.includes('danger')?'danger':cls.includes('warning')?'warning':'success'}"><i class="fa-solid ${text==='Đã trả'?'fa-check':'fa-book'}"></i></span><span class="reader-time-main"><strong>PM${String(loan.MaPhieuMuon).padStart(3,'0')} • ${escapeHTML(loan.SachMuon||'--')}</strong><small>Mượn ${formatLibraryDate(loan.NgayMuon)}${loan.HanTra?` • Hạn ${formatLibraryDate(loan.HanTra)}`:''}</small></span><span class="reader-time-status ${cls}">${text}</span><i class="fa-solid fa-chevron-right"></i></button>` : `<tr class="dashboard-data-row" data-open-page="muontra.php"><td>PM${String(loan.MaPhieuMuon).padStart(3, '0')}</td><td>${escapeHTML(loan.DocGia || '--')}</td><td>${escapeHTML(loan.SachMuon || '--')}</td><td>${formatLibraryDate(loan.NgayMuon)}</td><td><span class="${cls}">${text}</span></td></tr>`;
                });
                renderRows(el('recentLoansBody'), recent, isStaff ? '<tr><td colspan="6">Chưa có phiếu mượn.</td></tr>' : isCustomer ? '<div class="reader-empty">Bạn chưa có phiếu mượn nào.</div>' : '<tr><td colspan="5">Chưa có phiếu mượn.</td></tr>');
            }

            if (isStaff) {
                ['totalBooks','totalReaders','borrowingBooks','overdueBooks'].forEach(id => {
                    const node = el(id); if (!node) return;
                    const target = Number(String(node.textContent || '0').replace(/[^0-9.-]/g,'')) || 0;
                    const start = performance.now(); const duration = 850;
                    const tick = now => { const p=Math.min(1,(now-start)/duration); node.textContent=Math.round(target*(1-Math.pow(1-p,3))).toLocaleString('vi-VN'); if(p<1)requestAnimationFrame(tick); };
                    node.textContent='0'; requestAnimationFrame(tick);
                });
            }

            document.querySelectorAll('[data-open-page]').forEach(row => {
                row.style.cursor = 'pointer';
                row.addEventListener('click', () => {
                    const target = row.dataset.openPage;
                    loadPage(target, target === 'muontra.php' ? 'Mượn - Trả' : 'Sách');
                });
            });
            if (isGuest) initGuestMotion();
            if (isCustomer) { bindReaderReveal(); animateReaderCounts(); const sub=el('readerOverdueSub'); if(sub) sub.textContent=Number(summary.overdueBooks||0)>0 ? 'Cần xử lý ngay' : 'Không có cảnh báo'; }
        } catch (error) {
            console.error('Không thể tải trang chủ:', error);
            renderRows(el('popularBooksBody'), [], isCustomer ? `<div class="reader-empty">${escapeHTML(error.message)}</div>` : `<tr><td colspan="3">${escapeHTML(error.message)}</td></tr>`);
            renderRows(el('newBooksBody'), [], isCustomer ? `<div class="reader-empty">${escapeHTML(error.message)}</div>` : `<tr><td colspan="3">${escapeHTML(error.message)}</td></tr>`);
            if (el('recentLoansBody')) renderRows(el('recentLoansBody'), [], `<tr><td colspan="5">${escapeHTML(error.message)}</td></tr>`);
        }
    }

    init();
})();


// === Dashboard nâng cao / gợi ý độc giả hiển thị ngay tại Trang chủ ===
(async function loadVisibleAdvancedHome(){
  const role=window.libraryAccess?.roleKey; if(!role || role==='guest') return;
  const esc=window.escapeHTML || (x=>String(x??''));
  async function get(action,params={}){const u=new URL('advanced_api.php',location.href);u.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>u.searchParams.set(k,v));const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Không tải được dữ liệu');return p.data}
  async function post(action,data={}){const r=await fetch('advanced_api.php?action='+encodeURIComponent(action),{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(data)});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Không cập nhật được');return p.data}
  const book=(x,fav=true)=>`<div class="home-book"><img src="${esc(x.HinhAnh||'')}" onerror="this.style.visibility='hidden'"><div class="info"><b>${esc(x.TenSach)}</b><small>${esc(x.TacGia||'')} • ${Number(x.SoLuong||0)>0?'Có sẵn':'Tạm hết'}</small></div>${fav?`<button data-home-fav="${x.MaSach}" title="Thêm/bỏ muốn đọc">♡</button>`:''}</div>`;
  try{
    if(role==='customer'){
      const [rec,wish,prof,loanPayload,bookPayload,forYou,journey]=await Promise.all([get('recommendations'),get('wishlist'),get('reading_profile'),libraryApi.get('loans'),libraryApi.get('books'),get('reader_for_you'),get('reader_journey')]);
      const r=document.getElementById('homeRecommendations'),w=document.getElementById('homeWishlist'),p=document.getElementById('homeReadingProfile');
      const makeCard=window.readerHomeBookCard || (x=>`<div class="home-book"><div class="info"><b>${esc(x.TenSach)}</b><small>${esc(x.TacGia||'')}</small></div></div>`);
      if(r){r.innerHTML=(rec||[]).slice(0,8).map(x=>makeCard(x,{heart:true,value:Number(x.SoLuong||0)>0?'Có sẵn':'Tạm hết'})).join('')||'<div class="reader-empty">Chưa đủ lịch sử để gợi ý. Hãy khám phá và mượn vài cuốn trước nhé.</div>';window.bindReaderHomeBookCards?.(r);}
      if(w){w.innerHTML=(wish||[]).slice(0,8).map(x=>makeCard(x,{heart:true,active:true,value:Number(x.SoLuong||0)>0?'Có sẵn':'Tạm hết'})).join('')||'<div class="reader-empty reader-empty-wishlist"><i class="fa-regular fa-heart"></i><strong>Danh sách muốn đọc đang trống</strong><span>Thả tim những cuốn bạn thích để lưu lại tại đây.</span></div>';window.bindReaderHomeBookCards?.(w);}
      if(p){
        const sm=prof.summary||{}, rate=Math.max(0,Math.min(100,Number(sm.TyLeDungHan??0)||0));
        p.innerHTML=`<div class="reader-profile-layout"><div class="reader-profile-ring" style="--reader-rate:${rate*3.6}deg"><div><strong>${rate}%</strong><span>đúng hạn</span></div></div><div class="reader-profile-stats"><div><strong>${sm.SoCuonNam||0}</strong><span>Cuốn năm nay</span></div><div><strong>${sm.LuotMuonNam||0}</strong><span>Lượt mượn</span></div><div><strong>${esc(prof.favorite?.TenTheLoai||'--')}</strong><span>Thể loại yêu thích</span></div><div><strong>${esc(prof.bestMonth?.Thang||'--')}</strong><span>Tháng đọc nhiều</span></div></div></div><div class="reader-badges">${(prof.badges||[]).map(x=>`<span>${esc(x)}</span>`).join('')||'<span>🌱 Hành trình đọc vừa bắt đầu</span>'}</div>`;
      }

      const loans=loanPayload?.data?.loans||[];
      const active=loans.filter(x=>!x.NgayTra && !['Chờ duyệt','Đã trả','Đã hủy','Hủy'].includes(String(x.TrangThai||'')));
      const loanBox=document.getElementById('readerCurrentLoans');
      if(loanBox) loanBox.innerHTML=active.length?active.slice(0,6).map(x=>window.readerHomeLoanCard?.(x)||'').join(''):'<div class="reader-empty reader-empty-loan"><i class="fa-solid fa-book-open"></i><strong>Hiện bạn chưa mượn cuốn nào</strong><span>Khám phá kho sách để bắt đầu hành trình đọc tiếp theo.</span><button type="button" onclick="loadPage(\'sach.php\',\'Sách\')">Khám phá sách</button></div>';

      const today=new Date();today.setHours(0,0,0,0);
      const overdue=active.filter(x=>x.HanTra && new Date(`${x.HanTra}T00:00:00`)<today);
      const soon=active.filter(x=>{if(!x.HanTra)return false;const d=Math.ceil((new Date(`${x.HanTra}T00:00:00`)-today)/86400000);return d>=0&&d<=3;});
      const alertBox=document.getElementById('readerDueAlert');
      if(alertBox && (overdue.length||soon.length)){alertBox.classList.remove('d-none');alertBox.innerHTML=`<i class="fa-solid ${overdue.length?'fa-triangle-exclamation':'fa-clock'}"></i><div><strong>${overdue.length?`${overdue.length} phiếu đang quá hạn`:`${soon.length} phiếu sắp đến hạn`}</strong><span>${overdue.length?'Vui lòng kiểm tra và hoàn trả sách sớm để tránh phát sinh thêm quá hạn.':'Có sách cần trả trong 3 ngày tới.'}</span></div><button type="button" onclick="loadPage('muontra.php','Mượn sách')">Kiểm tra ngay</button>`;}
      const live=document.getElementById('readerLiveNote');if(live){live.innerHTML=overdue.length?`<i class="fa-solid fa-triangle-exclamation"></i><span>Bạn có ${overdue.length} phiếu cần xử lý quá hạn.</span>`:active.length?`<i class="fa-solid fa-book-open"></i><span>Bạn đang có ${active.length} phiếu trong hành trình đọc.</span>`:`<i class="fa-solid fa-sparkles"></i><span>Kho sách đang chờ bạn khám phá hôm nay.</span>`;}

      const dateWeeks=[...new Set(loans.filter(x=>x.NgayMuon).map(x=>{const d=new Date(`${x.NgayMuon}T00:00:00`);const first=new Date(d);first.setDate(d.getDate()-((d.getDay()+6)%7));return first.toISOString().slice(0,10)}))].sort().reverse();
      let streak=0;if(dateWeeks.length){let cursor=new Date();cursor.setHours(0,0,0,0);cursor.setDate(cursor.getDate()-((cursor.getDay()+6)%7));for(let i=0;i<10;i++){const k=cursor.toISOString().slice(0,10);if(dateWeeks.includes(k))streak++;else if(i>0)break;cursor.setDate(cursor.getDate()-7);}}
      const sh=document.getElementById('readerStreakHero');if(sh)sh.textContent=streak?`${streak} tuần`:`${Number(prof.summary?.SoCuonNam||0)} cuốn`;

      const categories=bookPayload?.data?.categories||[];
      const genreBox=document.getElementById('readerGenreChips');
      if(genreBox){const favorite=prof.favorite?.TenTheLoai||'';const ranked=[...categories].sort((a,b)=>String(a.TenTheLoai||'').localeCompare(String(b.TenTheLoai||''),'vi'));if(favorite){ranked.sort((a,b)=>(b.TenTheLoai===favorite)-(a.TenTheLoai===favorite));}genreBox.innerHTML=ranked.slice(0,10).map((c,i)=>`<button type="button" class="reader-genre-chip ${i===0&&favorite?'favorite':''}" data-reader-category="${esc(c.MaTheLoai||'')}"><i class="fa-solid ${i===0&&favorite?'fa-heart':'fa-bookmark'}"></i><span>${esc(c.TenTheLoai||'Thể loại')}</span></button>`).join('');genreBox.querySelectorAll('[data-reader-category]').forEach(btn=>btn.onclick=()=>{sessionStorage.setItem('guestBookCategory',btn.dataset.readerCategory||'');sessionStorage.removeItem('guestBookSearch');loadPage('sach.php','Sách')});}

      const fillShelf=(id,items,empty)=>{const box=document.getElementById(id);if(!box)return;box.innerHTML=(items||[]).slice(0,8).map(x=>makeCard(x,{heart:true,value:Number(x.SoLuong||0)>0?'Có sẵn':'Tạm hết'})).join('')||`<div class="reader-empty">${esc(empty)}</div>`;window.bindReaderHomeBookCards?.(box)};
      const becauseTitle=document.getElementById('readerBecauseTitle');if(becauseTitle)becauseTitle.textContent=forYou?.favorite?.TenTheLoai?`Vì bạn thích ${forYou.favorite.TenTheLoai}`:'Vì bạn thích...';
      fillShelf('readerBecauseShelf',forYou?.because,'Chưa đủ lịch sử theo thể loại để tạo gợi ý.');
      fillShelf('readerNextShelf',forYou?.next,'Danh sách muốn đọc đang trống.');
      fillShelf('readerSimilarShelf',forYou?.similar,'Chưa đủ dữ liệu từ độc giả cùng gu.');

      const jt=document.getElementById('readerJourneyTimeline');if(jt){const rows=(journey?.history||[]).slice(0,8);jt.innerHTML=rows.map(x=>{const overdue=!x.NgayTra&&x.HanTra&&new Date(`${x.HanTra}T00:00:00`)<today;const done=!!x.NgayTra;const status=done?'Đã trả':overdue?'Quá hạn':'Đang đọc';const icon=done?'fa-check':overdue?'fa-triangle-exclamation':'fa-book-open';return `<div class="journey-item"><span class="journey-icon ${done?'done':overdue?'overdue':''}"><i class="fa-solid ${icon}"></i></span><div class="journey-copy"><strong>${esc(String(x.Sach||'--').split('||').join(', '))}</strong><small>Mượn ${window.formatLibraryDate?formatLibraryDate(x.NgayMuon):esc(x.NgayMuon||'--')}${x.HanTra?` • Hạn ${window.formatLibraryDate?formatLibraryDate(x.HanTra):esc(x.HanTra)}`:''}</small></div><span class="journey-status">${status}</span></div>`}).join('')||'<div class="reader-empty">Hành trình đọc sẽ xuất hiện sau lần mượn đầu tiên.</div>'}
      const jm=document.getElementById('readerJourneyMonths');if(jm){const months=journey?.monthly||[];const max=Math.max(1,...months.map(x=>Number(x.SoCuon||0)));jm.innerHTML=months.length?months.slice(-8).map(x=>`<div class="month-bar"><span>${esc(x.label||'')}</span><span class="month-bar-track"><i style="width:${Math.round(Number(x.SoCuon||0)/max*100)}%"></i></span><b>${Number(x.SoCuon||0)}</b></div>`).join(''):'<div class="reader-empty">Chưa có dữ liệu theo tháng.</div>'}
      const ms=document.getElementById('readerMilestones');if(ms){ms.innerHTML=(journey?.milestones||[]).map(x=>`<div class="milestone-chip"><i class="fa-solid fa-trophy"></i><div><strong>${esc(x.label)}</strong><div style="font-size:11px;color:#94a3b8">Đã đạt ${Number(x.count||0)} cuốn</div></div></div>`).join('')||'<div class="reader-empty">Cột mốc đầu tiên đang chờ bạn.</div>'}

      document.querySelectorAll('[data-home-fav]').forEach(b=>b.onclick=async(e)=>{e.stopPropagation();try{await post('wishlist_toggle',{bookId:+b.dataset.homeFav});window.libraryToast?.('Đã cập nhật danh sách muốn đọc.');loadPage('trangchu.php','Trang chủ')}catch(err){window.libraryToast?.(err.message,'error')||alert(err.message)}});
    } else {
      const [d,home]=await Promise.all([get('overview'),get('home_dashboard')]);
      const s=d.summary||{}, today=home.today||{};
      const m=document.getElementById('managerAdvancedMetrics'),a=document.getElementById('managerAlerts');
      const metricData=[
        {icon:'fa-arrows-rotate',label:'Đang mượn',value:s.borrowedCopies||0,tone:'blue',action:()=>loadPage('muontra.php','Mượn - Trả')},
        {icon:'fa-triangle-exclamation',label:'Quá hạn',value:s.overdueLoans||0,tone:'red',action:()=>loadPage('quahan.php','Quá hạn')},
        {icon:'fa-chair',label:'Đặt trước',value:s.waitingReservations||0,tone:'violet',action:()=>loadPage('muontra.php','Mượn - Trả')},
        {icon:'fa-book',label:'Hết sách',value:s.zeroStock||0,tone:'orange',action:()=>loadPage('sach.php','Sách')},
        {icon:'fa-coins',label:'Phạt chưa thu',value:Number(s.unpaidFines||0).toLocaleString('vi-VN')+'đ',tone:'amber',action:()=>openSmartTab('fines','Thư viện thông minh')},
        {icon:'fa-user-clock',label:'Ca thiếu người',value:s.tomorrowUnderstaffed||0,tone:'purple',action:()=>loadPage('calamviec.php','Ca làm việc')}
      ];
      if(m){m.innerHTML=metricData.map((x,i)=>`<button type="button" class="staff-metric-card tone-${x.tone}" data-staff-metric="${i}"><i class="fa-solid ${x.icon}"></i><span>${esc(x.label)}</span><strong>${esc(x.value)}</strong><small>Xem chi tiết <i class="fa-solid fa-arrow-right"></i></small></button>`).join('');m.querySelectorAll('[data-staff-metric]').forEach(btn=>btn.onclick=()=>metricData[+btn.dataset.staffMetric]?.action?.());}
      if(a){
        const al=[];
        if(+s.overdueLoans)al.push({icon:'fa-triangle-exclamation',tone:'danger',title:`${s.overdueLoans} phiếu đang quá hạn`,desc:'Ưu tiên kiểm tra và xử lý các phiếu quá hạn.',page:'quahan.php',pageTitle:'Quá hạn'});
        if(+s.zeroStock)al.push({icon:'fa-book',tone:'warning',title:`${s.zeroStock} đầu sách đã hết`,desc:'Kiểm tra tồn kho hoặc lập kế hoạch bổ sung.',page:'sach.php',pageTitle:'Sách'});
        if(+s.waitingReservations)al.push({icon:'fa-clock',tone:'info',title:`${s.waitingReservations} lượt đang chờ sách`,desc:'Có độc giả đang chờ được cấp sách.',page:'muontra.php',pageTitle:'Mượn - Trả'});
        if(+s.tomorrowUnderstaffed)al.push({icon:'fa-users',tone:'violet',title:`${s.tomorrowUnderstaffed} ca ngày mai thiếu người`,desc:'Cần kiểm tra lịch làm việc và phân công.',page:'calamviec.php',pageTitle:'Ca làm việc'});
        if(+s.unpaidFines)al.push({icon:'fa-coins',tone:'warning',title:`${Number(s.unpaidFines).toLocaleString('vi-VN')}đ tiền phạt chưa thu`,desc:'Theo dõi các khoản phạt chưa hoàn tất.',smart:'fines'});
        a.innerHTML=al.length?al.map((x,i)=>`<button type="button" class="staff-alert-card alert-${x.tone}" data-alert-index="${i}"><i class="fa-solid ${x.icon}"></i><span><strong>${esc(x.title)}</strong><small>${esc(x.desc)}</small></span><b><i class="fa-solid fa-chevron-right"></i></b></button>`).join(''):'<div class="staff-no-alert"><i class="fa-solid fa-circle-check"></i><div><strong>Không có cảnh báo đáng chú ý</strong><span>Hệ thống đang vận hành ổn định.</span></div></div>';
        a.querySelectorAll('[data-alert-index]').forEach(btn=>btn.onclick=()=>{const x=al[+btn.dataset.alertIndex];if(x.smart)openSmartTab(x.smart,'Thư viện thông minh');else loadPage(x.page,x.pageTitle);});
      }

      const setText=(id,value)=>{const n=document.getElementById(id);if(n)n.textContent=value};
      const date=new Date();setText('staffTodayLabel',new Intl.DateTimeFormat('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'}).format(date));
      setText('todayBorrowed',Number(today.borrowedToday||0).toLocaleString('vi-VN'));setText('todayReturned',Number(today.returnedToday||0).toLocaleString('vi-VN'));setText('todayOverdue',Number(today.overdueNow||0).toLocaleString('vi-VN'));
      const totalToday=Number(today.borrowedToday||0)+Number(today.returnedToday||0);setText('staffTodayTotal',totalToday.toLocaleString('vi-VN'));setText('staffSystemStatus','Đang hoạt động');setText('staffDbState',home.system?.database||'Đã kết nối');setText('staffPhpState',home.system?.php||'--');
      const ring=document.getElementById('staffActivityRing');if(ring){const level=Math.min(100,Math.max(12,totalToday*8));ring.style.setProperty('--ring-level',`${level}%`);}

      const activity=document.getElementById('staffRecentActivity');
      if(activity){
        const rows=home.activity||[];
        activity.innerHTML=rows.length?rows.slice(0,7).map((x,i)=>`<div class="staff-timeline-item"><span class="staff-timeline-dot"></span><div><strong>${esc(x.HanhDong||'Hoạt động hệ thống')}</strong><p>${esc([x.NguoiThucHien,x.DoiTuong,x.ChiTiet].filter(Boolean).join(' • '))}</p><small>${esc(x.NgayTao||'')}</small></div></div>`).join(''):'<div class="staff-empty">Chưa có hoạt động nào được ghi nhận.</div>';
      }

      const chartNode=document.getElementById('staffSevenDayChart');
      if(chartNode && window.Chart){
        const rows=home.days||[]; if(window.staffSevenDayChartInstance)window.staffSevenDayChartInstance.destroy();
        window.staffSevenDayChartInstance=new Chart(chartNode.getContext('2d'),{type:'line',data:{labels:rows.map(x=>x.label),datasets:[{label:'Mượn',data:rows.map(x=>+x.borrow||0),borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.10)',tension:.38,fill:true,pointRadius:3,pointHoverRadius:5},{label:'Trả',data:rows.map(x=>+x.returned||0),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.06)',tension:.38,fill:true,pointRadius:3,pointHoverRadius:5},{label:'Quá hạn',data:rows.map(x=>+x.overdue||0),borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.05)',tension:.38,fill:true,pointRadius:3,pointHoverRadius:5}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:950,easing:'easeOutQuart'},interaction:{mode:'index',intersect:false},plugins:{legend:{display:false},datalabels:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#64748b'}},y:{beginAtZero:true,ticks:{precision:0,color:'#64748b'},grid:{color:'rgba(148,163,184,.16)'}}}}});
      }

      const access=window.libraryAccess||{};const statsBtn=document.querySelector('[data-staff-statistics]');if(statsBtn && role==='employee'){statsBtn.disabled=true;statsBtn.classList.add('is-disabled');statsBtn.title='Tài khoản nhân viên không có quyền xem Thống kê.';}
      if(role==='employee'){
        document.querySelectorAll('#staffQuickActions button').forEach(btn=>{const text=btn.textContent||'';if(text.includes('Kho & kiểm kê')||text.includes('Nhật ký hoạt động')){btn.disabled=true;btn.classList.add('is-disabled');btn.title='Chức năng này cần quyền quản lý.';}});
      }
    }
  }catch(e){console.error(e);['managerAdvancedMetrics','managerAlerts','homeRecommendations','homeWishlist','homeReadingProfile'].forEach(id=>{const n=document.getElementById(id);if(n)n.innerHTML=`<div class="adv-row">${esc(e.message)}</div>`})}
})();
