/**
/**
 * Leaderboard Interactive JavaScript Logic (Dev 5)
 * triBBBal Social Media Platform - Leaderboard Module
 *
 * Scope: Connects rendered UI (Dev 3 & 4) to JSON API (Dev 2) via fetch calls.
 * Manages view switching (Top 7 vs Full Rankings), real-time debounced search,
 * time filter pills, table rendering, pagination, and sticky progress bar updates.
 */

(function () {
    'use strict';

    // Global Config & State
    const CONFIG = {
        apiBase: 'xhr/leaderboard.php',
        fallbackApiBase: '/api/leaderboard',
        currentUserId: window.TRIBBBAL_USER_ID || null,
        itemsPerPage: 15,
        debounceDelay: 300,
        kingGoalTokens: 100000
    };

    const state = {
        currentView: 'top7', // 'top7' | 'full'
        activeFilter: 'all-time', // 'all-time' | 'this-month' | 'this-week' | 'today'
        searchQuery: '',
        currentPage: 1,
        totalPages: 1,
        totalMembers: 1247832,
        userProgress: null,
        isLoading: false
    };

    // Built-in Demo Data Fallback (for QA & offline testing before Dev 2 API is fully live)
    const DEMO_DATA = {
        top7: [
            { rank: 1, name: 'KingMaverick', username: 'kingmaverick', tokens: 168760, token_count: 168760, status: 'KING', avatar: 'https://ui-avatars.com/api/?name=King+Maverick&background=d97706&color=fff', joined: 'Jan 2020', trend: 'up' },
            { rank: 2, name: 'Ugochukwu Ogbodo', username: 'ugochukwu', tokens: 56890, token_count: 56890, status: 'KING', avatar: 'https://ui-avatars.com/api/?name=Ugochukwu+Ogbodo&background=0284c7&color=fff', joined: 'Mar 2021', trend: 'up' },
            { rank: 3, name: 'Christie Udoeyoh', username: 'christie', tokens: 55020, token_count: 55020, status: 'QUEEN', avatar: 'https://ui-avatars.com/api/?name=Christie+Udoeyoh&background=ec4899&color=fff', joined: 'Feb 2021', trend: 'up' },
            { rank: 4, name: 'Kene PL', username: 'kene_pl', tokens: 41380, token_count: 41380, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Kene+PL&background=4b5563&color=fff', joined: 'Jun 2021', trend: 'flat' },
            { rank: 5, name: 'Martha Okpara', username: 'martha_o', tokens: 40890, token_count: 40890, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Martha+Okpara&background=4b5563&color=fff', joined: 'Aug 2021', trend: 'up' },
            { rank: 6, name: 'Chidi K.', username: 'chidi_k', tokens: 38650, token_count: 38650, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Chidi+K&background=4b5563&color=fff', joined: 'Sep 2021', trend: 'down' },
            { rank: 7, name: 'Amaka V.', username: 'amaka_v', tokens: 36420, token_count: 36420, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Amaka+V&background=4b5563&color=fff', joined: 'Oct 2021', trend: 'flat' }
        ],
        rankings: [
            { rank: 1, name: 'KingMaverick', username: 'kingmaverick', tokens: 168760, token_count: 168760, status: 'KING', avatar: 'https://ui-avatars.com/api/?name=King+Maverick&background=d97706&color=fff', joined: 'Jan 2020', trend: 'up' },
            { rank: 2, name: 'Ugochukwu Ogbodo', username: 'ugochukwu', tokens: 56890, token_count: 56890, status: 'KING', avatar: 'https://ui-avatars.com/api/?name=Ugochukwu+Ogbodo&background=0284c7&color=fff', joined: 'Mar 2021', trend: 'up' },
            { rank: 3, name: 'Christie Udoeyoh', username: 'christie', tokens: 55020, token_count: 55020, status: 'QUEEN', avatar: 'https://ui-avatars.com/api/?name=Christie+Udoeyoh&background=ec4899&color=fff', joined: 'Feb 2021', trend: 'up' },
            { rank: 4, name: 'Kene PL', username: 'kene_pl', tokens: 41380, token_count: 41380, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Kene+PL&background=4b5563&color=fff', joined: 'Jun 2021', trend: 'flat' },
            { rank: 5, name: 'Martha Okpara', username: 'martha_o', tokens: 40890, token_count: 40890, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Martha+Okpara&background=4b5563&color=fff', joined: 'Aug 2021', trend: 'up' },
            { rank: 6, name: 'Chidi K.', username: 'chidi_k', tokens: 38650, token_count: 38650, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Chidi+K&background=4b5563&color=fff', joined: 'Sep 2021', trend: 'down' },
            { rank: 7, name: 'Amaka V.', username: 'amaka_v', tokens: 36420, token_count: 36420, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Amaka+V&background=4b5563&color=fff', joined: 'Oct 2021', trend: 'flat' },
            { rank: 8, name: 'Ade Bello', username: 'ade_bello', tokens: 34200, token_count: 34200, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Ade+Bello&background=4b5563&color=fff', joined: 'Nov 2021', trend: 'up' },
            { rank: 9, name: 'Tunde O.', username: 'tunde_o', tokens: 31500, token_count: 31500, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Tunde+O&background=4b5563&color=fff', joined: 'Dec 2021', trend: 'down' },
            { rank: 10, name: 'Chioma N.', username: 'chioma_n', tokens: 29800, token_count: 29800, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Chioma+N&background=4b5563&color=fff', joined: 'Jan 2022', trend: 'up' },
            { rank: 11, name: 'Darey A.', username: 'darey_a', tokens: 27400, token_count: 27400, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Darey+A&background=4b5563&color=fff', joined: 'Feb 2022', trend: 'flat' },
            { rank: 12, name: 'Kemi Alade', username: 'kemi_alade', tokens: 25100, token_count: 25100, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Kemi+Alade&background=4b5563&color=fff', joined: 'Mar 2022', trend: 'up' },
            { rank: 13, name: 'Emeka Nwosu', username: 'emeka_n', tokens: 22800, token_count: 22800, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Emeka+Nwosu&background=4b5563&color=fff', joined: 'Apr 2022', trend: 'down' },
            { rank: 14, name: 'Zainab Balogun', username: 'zainab_b', tokens: 19500, token_count: 19500, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Zainab+Balogun&background=4b5563&color=fff', joined: 'May 2022', trend: 'flat' },
            { rank: 15, name: 'Funke Akindele', username: 'funke_a', tokens: 17200, token_count: 17200, status: 'MEMBER', avatar: 'https://ui-avatars.com/api/?name=Funke+Akindele&background=4b5563&color=fff', joined: 'Jun 2022', trend: 'up' }
        ],
        user: {
            rank: 4521,
            tokens: 12450,
            status: 'MEMBER',
            name: 'You',
            avatar: 'https://ui-avatars.com/api/?name=You&background=e50914&color=fff',
            progress_pct: 12.45,
            tokens_remaining: 87550
        }
    };

    // Helper Utility Functions
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('en-US');
    }

    function sanitizeText(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getInitials(name) {
        if (!name) return '?';
        return name.trim().split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // View Switching Logic
    function switchView(viewName) {
        const top7Container = document.getElementById('leaderboard-view-top7');
        const fullContainer = document.getElementById('leaderboard-view-full');

        if (!top7Container || !fullContainer) return;

        state.currentView = viewName;

        if (viewName === 'full') {
            top7Container.classList.remove('is-active');
            top7Container.setAttribute('hidden', 'true');
            fullContainer.classList.add('is-active');
            fullContainer.removeAttribute('hidden');

            // Load rankings if switching to full view
            fetchFullRankings();
        } else {
            fullContainer.classList.remove('is-active');
            fullContainer.setAttribute('hidden', 'true');
            top7Container.classList.add('is-active');
            top7Container.removeAttribute('hidden');

            // Reload top 7 data
            fetchTop7();
        }
    }

    // API Data Fetching Methods
    async function fetchTop7() {
        state.isLoading = true;
        const filter = state.activeFilter.replace('-', '');
        const primaryUrl = `${CONFIG.apiBase}?action=top7&filter=${encodeURIComponent(filter)}`;
        const fallbackUrl = `${CONFIG.fallbackApiBase}/top7?filter=${encodeURIComponent(filter)}`;

        try {
            let response = await fetch(primaryUrl);
            if (!response.ok) {
                response = await fetch(fallbackUrl);
            }
            if (response.ok) {
                const json = await response.json();
                const data = json.data || (Array.isArray(json) ? json : null);
                if (data && data.length > 0) {
                    renderTop7View(data);
                    state.isLoading = false;
                    return;
                }
            }
        } catch (e) {
            console.warn('[Leaderboard] Primary XHR failed, using fallback demo data:', e);
        }

        // Fallback to Demo Data
        renderTop7View(DEMO_DATA.top7);
        state.isLoading = false;
    }

    async function fetchFullRankings() {
        state.isLoading = true;
        const filter = state.activeFilter.replace('-', '');
        const search = state.searchQuery;
        const page = state.currentPage;
        const limit = CONFIG.itemsPerPage;

        const primaryUrl = `${CONFIG.apiBase}?action=rankings&filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&page=${page}&limit=${limit}`;
        const fallbackUrl = `${CONFIG.fallbackApiBase}/rankings?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&page=${page}&limit=${limit}`;

        try {
            let response = await fetch(primaryUrl);
            if (!response.ok) {
                response = await fetch(fallbackUrl);
            }
            if (response.ok) {
                const json = await response.json();
                if (json.data && Array.isArray(json.data)) {
                    state.totalMembers = json.total || state.totalMembers;
                    state.totalPages = json.totalPages || Math.ceil(state.totalMembers / limit);
                    renderRankingsTable(json.data);
                    renderPagination();
                    updateTotalMembersCount();
                    state.isLoading = false;
                    return;
                }
            }
        } catch (e) {
            console.warn('[Leaderboard] Rankings XHR failed, using fallback demo data:', e);
        }

        // Fallback logic for demo data filtering/search/pagination
        let filtered = DEMO_DATA.rankings;
        if (search) {
            const q = search.toLowerCase();
            filtered = filtered.filter(item => (item.name || '').toLowerCase().includes(q) || (item.username || '').toLowerCase().includes(q));
        }

        state.totalMembers = search ? filtered.length : 1247832;
        state.totalPages = Math.max(1, Math.ceil(filtered.length / limit));
        const startIndex = (page - 1) * limit;
        const pageData = filtered.slice(startIndex, startIndex + limit);

        renderRankingsTable(pageData);
        renderPagination();
        updateTotalMembersCount();
        state.isLoading = false;
    }

    async function fetchUserProgress() {
        if (!CONFIG.currentUserId) {
            updateStickyProgressBar(DEMO_DATA.user);
            return;
        }

        const primaryUrl = `${CONFIG.apiBase}?action=user&id=${CONFIG.currentUserId}`;
        const fallbackUrl = `${CONFIG.fallbackApiBase}/user/${CONFIG.currentUserId}`;

        try {
            let response = await fetch(primaryUrl);
            if (!response.ok) {
                response = await fetch(fallbackUrl);
            }
            if (response.ok) {
                const json = await response.json();
                if (json && (json.rank || json.tokens)) {
                    state.userProgress = json;
                    updateStickyProgressBar(json);
                    return;
                }
            }
        } catch (e) {
            console.warn('[Leaderboard] User progress XHR failed, using fallback:', e);
        }

        updateStickyProgressBar(DEMO_DATA.user);
    }

    // DOM Rendering Methods
    function renderTop7View(items) {
        // Render Podium (Ranks 1, 2, 3)
        const podiumPlaces = document.querySelectorAll('.podium__place');
        podiumPlaces.forEach(el => {
            const rank = parseInt(el.getAttribute('data-rank'), 10);
            const item = items.find(i => i.rank === rank);
            if (item) {
                const avatarEl = el.querySelector('.podium__avatar img, .podium__avatar span');
                if (avatarEl) {
                    if (item.avatar) {
                        avatarEl.outerHTML = `<img src="${sanitizeText(item.avatar)}" alt="${sanitizeText(item.name)}">`;
                    } else {
                        avatarEl.outerHTML = `<span>${getInitials(item.name || item.username)}</span>`;
                    }
                }
                const nameEl = el.querySelector('.podium__username');
                if (nameEl) nameEl.textContent = item.name || item.username;

                const tokensEl = el.querySelector('.podium__tokens');
                if (tokensEl) tokensEl.textContent = `${formatNumber(item.tokens || item.token_count)} tokens`;
            }
        });

        // Render standard rank cards (4-7)
        const rankCards = document.querySelectorAll('.rank-card');
        const standardItems = items.filter(i => i.rank >= 4 && i.rank <= 7);

        rankCards.forEach((card, idx) => {
            const item = standardItems[idx];
            if (item) {
                const rankNumEl = card.querySelector('.rank-card__rank');
                if (rankNumEl) rankNumEl.textContent = `#${item.rank}`;

                const avatarWrap = card.querySelector('.rank-card__avatar');
                if (avatarWrap) {
                    if (item.avatar) {
                        avatarWrap.innerHTML = `<img src="${sanitizeText(item.avatar)}" alt="${sanitizeText(item.name)}">`;
                    } else {
                        avatarWrap.innerHTML = `<span>${getInitials(item.name || item.username)}</span>`;
                    }
                }

                const usernameEl = card.querySelector('.rank-card__username');
                if (usernameEl) usernameEl.textContent = item.name || item.username;

                const tokensEl = card.querySelector('.rank-card__tokens');
                if (tokensEl) tokensEl.textContent = formatNumber(item.tokens || item.token_count);
            }
        });
    }

    function renderRankingsTable(rows) {
        const tbody = document.getElementById('rankings-tbody');
        if (!tbody) return;

        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-secondary, #b3b3b3);">
                        No members found matching "${sanitizeText(state.searchQuery)}".
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map(row => {
            const rank = row.rank;
            let crownHtml = '';
            if (rank === 1) crownHtml = '<span class="crown gold">👑</span> ';
            else if (rank === 2) crownHtml = '<span class="crown silver">👑</span> ';
            else if (rank === 3) crownHtml = '<span class="crown bronze">👑</span> ';

            const status = (row.status || 'MEMBER').toUpperCase();
            let badgeClass = 'member';
            let badgeText = '👤 MEMBER';
            if (status === 'KING') { badgeClass = 'king'; badgeText = '👑 KING'; }
            else if (status === 'QUEEN') { badgeClass = 'queen'; badgeText = '👑 QUEEN'; }

            const trend = row.trend || 'flat';
            let trendSymbol = '&rarr;';
            let trendClass = 'neutral';
            if (trend === 'up') { trendSymbol = '&uarr;'; trendClass = 'up'; }
            else if (trend === 'down') { trendSymbol = '&darr;'; trendClass = 'down'; }

            const isCurrentUser = CONFIG.currentUserId && (row.user_id == CONFIG.currentUserId || row.is_current_user);

            return `
                <tr class="${isCurrentUser ? 'highlight-row' : ''}">
                    <td class="rank-col">${crownHtml}${rank}</td>
                    <td class="member-col">
                        <img src="${sanitizeText(row.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.name || 'User'))}" class="avatar" alt="User">
                        ${sanitizeText(row.name || row.username)}
                    </td>
                    <td class="tokens-col">${formatNumber(row.tokens || row.token_count)}</td>
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                    <td>${sanitizeText(row.joined || 'Jan 2022')}</td>
                    <td class="trend-col ${trendClass}">${trendSymbol}</td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination() {
        const container = document.getElementById('pagination-numbers');
        const prevBtn = document.getElementById('page-prev');
        const nextBtn = document.getElementById('page-next');

        if (prevBtn) prevBtn.disabled = (state.currentPage <= 1);
        if (nextBtn) nextBtn.disabled = (state.currentPage >= state.totalPages);

        if (!container) return;

        const total = state.totalPages;
        const current = state.currentPage;
        const pages = [];

        if (total <= 7) {
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            pages.push(1);
            if (current > 3) pages.push('...');
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (current < total - 2) pages.push('...');
            pages.push(total);
        }

        container.innerHTML = pages.map(p => {
            if (p === '...') return `<span class="dots">...</span>`;
            const isActive = p === current ? 'active' : '';
            return `<button class="page-num ${isActive}" data-page="${p}">${p}</button>`;
        }).join('');
    }

    function updateTotalMembersCount() {
        const countEl = document.getElementById('total-members-count');
        if (countEl) {
            countEl.textContent = formatNumber(state.totalMembers);
        }
    }

    function updateStickyProgressBar(userData) {
        const tokensEl = document.getElementById('lb-user-tokens');
        const rankEl = document.getElementById('lb-user-rank');
        const progressBarEl = document.getElementById('lb-progress-bar');

        const tokens = userData.tokens || userData.token_count || 0;
        const rank = userData.rank || '--';
        const progressPct = userData.progress_pct || Math.min(100, Math.round((tokens / CONFIG.kingGoalTokens) * 100));

        if (tokensEl) tokensEl.textContent = `${formatNumber(tokens)} Tokens`;
        if (rankEl) rankEl.textContent = `Rank #${formatNumber(rank)}`;
        if (progressBarEl) progressBarEl.style.width = `${progressPct}%`;
    }

    // Event Listeners Setup
    function initEventListeners() {
        // View Switching Buttons
        const backBtn = document.getElementById('btn-back-top7');
        if (backBtn) {
            backBtn.addEventListener('click', () => switchView('top7'));
        }

        const viewFullBtn = document.getElementById('btn-view-full-rankings');
        if (viewFullBtn) {
            viewFullBtn.addEventListener('click', () => switchView('full'));
        }

        const viewMyRankBtn = document.getElementById('btn-view-my-rank');
        if (viewMyRankBtn) {
            viewMyRankBtn.addEventListener('click', () => {
                switchView('full');
                setTimeout(() => {
                    const userRow = document.getElementById('user-highlight-row');
                    if (userRow) userRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 200);
            });
        }

        // Search Input with Debounce
        const searchInput = document.getElementById('search-members');
        if (searchInput) {
            const handleSearch = debounce((e) => {
                state.searchQuery = e.target.value.trim();
                state.currentPage = 1;
                fetchFullRankings();
            }, CONFIG.debounceDelay);

            searchInput.addEventListener('input', handleSearch);
        }

        // Time Filter Pills
        const filterPillsContainer = document.getElementById('time-filters');
        if (filterPillsContainer) {
            filterPillsContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('.pill');
                if (!btn) return;

                filterPillsContainer.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');

                state.activeFilter = btn.getAttribute('data-filter') || 'all-time';
                state.currentPage = 1;

                if (state.currentView === 'full') {
                    fetchFullRankings();
                } else {
                    fetchTop7();
                }
            });
        }

        // Pagination Click Delegation
        const paginationWrapper = document.querySelector('.pagination-wrapper');
        if (paginationWrapper) {
            paginationWrapper.addEventListener('click', (e) => {
                const pageNumBtn = e.target.closest('.page-num');
                const prevBtn = e.target.closest('#page-prev');
                const nextBtn = e.target.closest('#page-next');

                if (pageNumBtn && !pageNumBtn.classList.contains('active')) {
                    const targetPage = parseInt(pageNumBtn.getAttribute('data-page'), 10);
                    if (targetPage) {
                        state.currentPage = targetPage;
                        fetchFullRankings();
                    }
                } else if (prevBtn && state.currentPage > 1) {
                    state.currentPage--;
                    fetchFullRankings();
                } else if (nextBtn && state.currentPage < state.totalPages) {
                    state.currentPage++;
                    fetchFullRankings();
                }
            });
        }
    }

    // Initialization
    function init() {
        initEventListeners();
        fetchTop7();
        fetchUserProgress();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

