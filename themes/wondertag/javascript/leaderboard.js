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
  "use strict";

  // Global Config & State
  const CONFIG = {
    apiBase: "xhr/leaderboard.php",
    fallbackApiBase: "/api/leaderboard",
    currentUserId: window.TRIBBBAL_USER_ID || null,
    itemsPerPage: 15,
    debounceDelay: 300,
    kingGoalTokens: 100000,
  };

  const state = {
    currentView: "top7", // 'top7' | 'full'
    activeFilter: "all-time", // 'all-time' | 'this-month' | 'this-week' | 'today'
    searchQuery: "",
    selectedTypes: ["0", "1", "2"], // Default all selected
    currentPage: 1,
    totalPages: 1,
    totalMembers: 0,
    userProgress: null,
    isLoading: false,
  };

  // Empty fallback — all data comes from the database via xhr/leaderboard.php
  const DEMO_DATA = {
    top7: [],
    rankings: [],
    user: { rank: 0, tokens: 0, status: "MEMBER", progress_pct: 0 },
  };

  // Helper Utility Functions
  function formatNumber(num) {
    if (num === null || num === undefined) return "0";
    return Number(num).toLocaleString("en-US");
  }

  function sanitizeText(str) {
    if (!str) return "";
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function getInitials(name) {
    if (!name) return "?";
    return name
      .trim()
      .split(" ")
      .map((n) => n[0])
      .join("")
      .substring(0, 2)
      .toUpperCase();
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
    const top7Container = document.getElementById("leaderboard-view-top7");
    const fullContainer = document.getElementById("leaderboard-view-full");

    if (!top7Container || !fullContainer) return;

    state.currentView = viewName;

    // Update URL so reload preserves the view
    const url = new URL(window.location);
    if (viewName === "full") {
      url.searchParams.set("type", "full");
      top7Container.classList.remove("is-active");
      top7Container.setAttribute("hidden", "true");
      fullContainer.classList.add("is-active");
      fullContainer.removeAttribute("hidden");

      // Load rankings if switching to full view
      fetchFullRankings();
    } else {
      url.searchParams.delete("type");
      fullContainer.classList.remove("is-active");
      fullContainer.setAttribute("hidden", "true");
      top7Container.classList.add("is-active");
      top7Container.removeAttribute("hidden");

      // Reload top 7 data
      fetchTop7();
    }
    window.history.replaceState({}, "", url);
  }

  // API Data Fetching Methods
  async function fetchTop7() {
    state.isLoading = true;
    const filter = state.activeFilter.replace("-", "");
    const types = state.selectedTypes.join(",");
    const primaryUrl = `${CONFIG.apiBase}?action=top7&filter=${encodeURIComponent(filter)}&account_types=${encodeURIComponent(types)}`;
    const fallbackUrl = `${CONFIG.fallbackApiBase}/top7?filter=${encodeURIComponent(filter)}&account_types=${encodeURIComponent(types)}`;

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
      console.warn(
        "[Leaderboard] Primary XHR failed, using fallback demo data:",
        e,
      );
    }

    // Fallback to Demo Data
    renderTop7View(DEMO_DATA.top7);
    state.isLoading = false;
  }

  async function fetchFullRankings() {
    state.isLoading = true;
    const filter = state.activeFilter.replace("-", "");
    const search = state.searchQuery;
    const page = state.currentPage;
    const limit = CONFIG.itemsPerPage;
    const types = state.selectedTypes.join(",");

    const primaryUrl = `${CONFIG.apiBase}?action=rankings&filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&page=${page}&limit=${limit}&account_types=${encodeURIComponent(types)}`;
    const fallbackUrl = `${CONFIG.fallbackApiBase}/rankings?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&page=${page}&limit=${limit}&account_types=${encodeURIComponent(types)}`;

    try {
      let response = await fetch(primaryUrl);
      if (!response.ok) {
        response = await fetch(fallbackUrl);
      }
      if (response.ok) {
        const json = await response.json();
        if (json.data && Array.isArray(json.data)) {
          state.totalMembers = json.total || state.totalMembers;
          state.totalPages =
            json.totalPages || Math.ceil(state.totalMembers / limit);
          renderRankingsTable(json.data);
          renderPagination();
          updateTotalMembersCount();
          state.isLoading = false;
          return;
        }
      }
    } catch (e) {
      console.warn(
        "[Leaderboard] Rankings XHR failed, using fallback demo data:",
        e,
      );
    }

    // Fallback logic for demo data filtering/search/pagination
    let filtered = DEMO_DATA.rankings;
    if (search) {
      const q = search.toLowerCase();
      filtered = filtered.filter(
        (item) =>
          (item.name || "").toLowerCase().includes(q) ||
          (item.username || "").toLowerCase().includes(q),
      );
    }

    state.totalMembers = filtered.length;
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
        const userData = json.data || json;
        if (userData && (userData.rank || userData.tokens)) {
          state.userProgress = userData;
          updateStickyProgressBar(userData);
          return;
        }
      }
    } catch (e) {
      console.warn(
        "[Leaderboard] User progress XHR failed, using fallback:",
        e,
      );
    }

    updateStickyProgressBar(DEMO_DATA.user);
  }

  // DOM Rendering Methods
  function renderTop7View(items) {
    // Render Podium (Ranks 1, 2, 3)
    const podiumPlaces = document.querySelectorAll(".podium__place");
    podiumPlaces.forEach((el) => {
      const rank = parseInt(el.getAttribute("data-rank"), 10);
      const item = items.find((i) => i.rank === rank);
      if (item) {
        const avatarEl = el.querySelector(
          ".podium__avatar img, .podium__avatar span",
        );
        if (avatarEl) {
          if (item.avatar) {
            avatarEl.outerHTML = `<img src="${sanitizeText(item.avatar)}" alt="${sanitizeText(item.name)}">`;
          } else {
            avatarEl.outerHTML = `<span>${getInitials(item.name || item.username)}</span>`;
          }
        }
        const nameEl = el.querySelector(".podium__username");
        if (nameEl) nameEl.textContent = item.name || item.username;

        const tokensEl = el.querySelector(".podium__tokens");
        if (tokensEl)
          tokensEl.textContent = `${formatNumber(item.tokens || item.token_count)}`;

        // Update status badge
        const statusEl = el.querySelector(".podium__badge");
        if (statusEl && item.status) {
          statusEl.textContent = item.status.toUpperCase();
          statusEl.style.display = "inline-block";
        } else if (statusEl) {
          statusEl.style.display = "none";
        }
      }
    });

    // Render standard rank cards (4-7)
    const rankCards = document.querySelectorAll(".rank-card");
    const standardItems = items.filter((i) => i.rank >= 4 && i.rank <= 7);

    rankCards.forEach((card, idx) => {
      const item = standardItems[idx];
      if (item) {
        const rankNumEl = card.querySelector(".rank-card__rank");
        if (rankNumEl) rankNumEl.textContent = item.rank;

        const avatarWrap = card.querySelector(".rank-card__avatar");
        if (avatarWrap) {
          if (item.avatar) {
            avatarWrap.innerHTML = `<img src="${sanitizeText(item.avatar)}" alt="${sanitizeText(item.name)}">`;
          } else {
            avatarWrap.innerHTML = `<span>${getInitials(item.name || item.username)}</span>`;
          }
        }

        const usernameEl = card.querySelector(".rank-card__username");
        if (usernameEl) usernameEl.textContent = item.name || item.username;

        const tokensEl = card.querySelector(".rank-card__tokens");
        if (tokensEl)
          tokensEl.textContent = formatNumber(item.tokens || item.token_count);
      }
    });
  }

  function renderRankingsTable(rows) {
    const tbody = document.getElementById("rankings-tbody");
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

    tbody.innerHTML = rows
      .map((row) => {
        const rank = row.rank;
        let crownHtml = "";
        if (rank === 1) crownHtml = '<span class="crown gold">👑</span> ';
        else if (rank === 2)
          crownHtml = '<span class="crown silver">👑</span> ';
        else if (rank === 3)
          crownHtml = '<span class="crown bronze">👑</span> ';

        const status = (row.status || "MEMBER").toUpperCase();
        let badgeClass = "member";
        let badgeText = "👤 MEMBER";
        if (status === "KING") {
          badgeClass = "king";
          badgeText = "👑 KING";
        } else if (status === "QUEEN") {
          badgeClass = "queen";
          badgeText = "👑 QUEEN";
        }

        const trend = row.trend || "flat";
        let trendSymbol = "&rarr;";
        let trendClass = "neutral";
        if (trend === "up") {
          trendSymbol = "&uarr;";
          trendClass = "up";
        } else if (trend === "down") {
          trendSymbol = "&darr;";
          trendClass = "down";
        }

        const isCurrentUser =
          CONFIG.currentUserId &&
          (row.user_id == CONFIG.currentUserId || row.is_current_user);

        return `
                <tr class="${isCurrentUser ? "highlight-row" : ""}">
                    <td class="rank-col">${crownHtml}${rank}</td>
                    <td class="member-col">
                        <img src="${sanitizeText(row.avatar || "https://ui-avatars.com/api/?name=" + encodeURIComponent(row.name || "User"))}" class="avatar" alt="User">
                        ${sanitizeText(row.name || row.username)}
                    </td>
                    <td class="tokens-col">${formatNumber(row.tokens || row.token_count)}</td>
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                    <td>${sanitizeText(row.joined || "Jan 2022")}</td>
                    <td class="trend-col ${trendClass}">${trendSymbol}</td>
                </tr>
            `;
      })
      .join("");
  }

  function renderPagination() {
    const container = document.getElementById("pagination-numbers");
    const prevBtn = document.getElementById("page-prev");
    const nextBtn = document.getElementById("page-next");

    if (prevBtn) prevBtn.disabled = state.currentPage <= 1;
    if (nextBtn) nextBtn.disabled = state.currentPage >= state.totalPages;

    if (!container) return;

    const total = state.totalPages;
    const current = state.currentPage;
    const pages = [];

    if (total <= 7) {
      for (let i = 1; i <= total; i++) pages.push(i);
    } else {
      pages.push(1);
      if (current > 3) pages.push("...");
      const start = Math.max(2, current - 1);
      const end = Math.min(total - 1, current + 1);
      for (let i = start; i <= end; i++) pages.push(i);
      if (current < total - 2) pages.push("...");
      pages.push(total);
    }

    container.innerHTML = pages
      .map((p) => {
        if (p === "...") return `<span class="dots">...</span>`;
        const isActive = p === current ? "active" : "";
        return `<button class="page-num ${isActive}" data-page="${p}">${p}</button>`;
      })
      .join("");
  }

  function updateTotalMembersCount() {
    const countEl = document.getElementById("total-members-count");
    if (countEl) {
      countEl.textContent = formatNumber(state.totalMembers);
    }
  }

  function updateStickyProgressBar(userData) {
    const tokensEl = document.getElementById("lb-user-tokens");
    const rankEl = document.getElementById("lb-user-rank");
    const progressBarEl = document.getElementById("lb-progress-bar");

    const tokens = userData.tokens || userData.token_count || 0;
    const rank = userData.rank || "--";
    const progressPct =
      userData.progress_pct ||
      Math.min(100, Math.round((tokens / CONFIG.kingGoalTokens) * 100));

    if (tokensEl) tokensEl.textContent = `${formatNumber(tokens)}`;
    if (rankEl) rankEl.textContent = `#${formatNumber(rank)}`;
    if (progressBarEl) progressBarEl.style.width = `${progressPct}%`;
    const percentEl = document.getElementById("lb-footer-progress-percent");
    if (percentEl) percentEl.textContent = `${progressPct}%`;
  }

  // Event Listeners Setup
  function initEventListeners() {
    // View Switching Buttons
    const backBtn = document.getElementById("btn-back-top7");
    if (backBtn) {
      backBtn.addEventListener("click", () => switchView("top7"));
    }

    const viewFullBtns = document.querySelectorAll(
      "#btn-view-full-rankings, .quick-filters__link",
    );
    viewFullBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        switchView("full");
      });
    });

    const viewMyRankBtn = document.getElementById("btn-view-my-rank");
    if (viewMyRankBtn) {
      viewMyRankBtn.addEventListener("click", () => {
        switchView("full");
        setTimeout(() => {
          const userRow = document.getElementById("user-highlight-row");
          if (userRow)
            userRow.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 200);
      });
    }

    // Search Input with Debounce
    const searchInput = document.getElementById("search-members");
    if (searchInput) {
      const handleSearch = debounce((e) => {
        state.searchQuery = e.target.value.trim();
        state.currentPage = 1;
        fetchFullRankings();
      }, CONFIG.debounceDelay);

      searchInput.addEventListener("input", handleSearch);
    }

    // Time Filter Pills
    const filterPillsContainers = document.querySelectorAll(".filter-pills");
    filterPillsContainers.forEach((container) => {
      container.addEventListener("click", (e) => {
        const btn = e.target.closest(".pill");
        if (!btn) return;

        const filter = btn.getAttribute("data-filter") || "all-time";
        state.activeFilter = filter;
        state.currentPage = 1;

        // Sync all pill containers
        document.querySelectorAll(".filter-pills").forEach((c) => {
          c.querySelectorAll(".pill").forEach((p) => {
            p.classList.toggle(
              "active",
              p.getAttribute("data-filter") === filter,
            );
          });
        });

        if (state.currentView === "full") {
          fetchFullRankings();
        } else {
          fetchTop7();
        }
      });
    });

    // Multiselect Filter Logic
    const typeWrappers = document.querySelectorAll(".multiselect-wrapper");

    typeWrappers.forEach((wrapper) => {
      const btn = wrapper.querySelector(".btn-type-filter");
      const dropdown = wrapper.querySelector(".dropdown-type-filter");
      const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');

      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        // Close others
        document.querySelectorAll(".dropdown-type-filter").forEach((d) => {
          if (d !== dropdown) d.classList.remove("is-active");
        });
        dropdown.classList.toggle("is-active");
      });

      checkboxes.forEach((cb) => {
        cb.addEventListener("change", () => {
          const selected = [];
          checkboxes.forEach((c) => {
            if (c.checked) selected.push(c.value);
          });
          state.selectedTypes = selected;
          state.currentPage = 1;

          // Sync all checkboxes
          document
            .querySelectorAll('.dropdown-type-filter input[type="checkbox"]')
            .forEach((allCb) => {
              allCb.checked = selected.includes(allCb.value);
            });

          // Update all button texts
          document
            .querySelectorAll(".btn-type-filter .btn-text")
            .forEach((txt) => {
              if (selected.length === 0) {
                txt.textContent = "None Selected";
              } else if (selected.length === checkboxes.length) {
                txt.textContent = "All User Types";
              } else {
                txt.textContent = `${selected.length} Selected`;
              }
            });

          if (state.currentView === "full") {
            fetchFullRankings();
          } else {
            fetchTop7();
          }
        });
      });
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest(".multiselect-wrapper")) {
        document
          .querySelectorAll(".dropdown-type-filter")
          .forEach((d) => d.classList.remove("is-active"));
      }
    });

    // Pagination Click Delegation
    const paginationWrapper = document.querySelector(".pagination-wrapper");
    if (paginationWrapper) {
      paginationWrapper.addEventListener("click", (e) => {
        const pageNumBtn = e.target.closest(".page-num");
        const prevBtn = e.target.closest("#page-prev");
        const nextBtn = e.target.closest("#page-next");

        if (pageNumBtn && !pageNumBtn.classList.contains("active")) {
          const targetPage = parseInt(pageNumBtn.getAttribute("data-page"), 10);
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

    // Detect if full view is active on page load (e.g. ?type=full)
    const fullContainer = document.getElementById("leaderboard-view-full");
    if (fullContainer && fullContainer.classList.contains("is-active")) {
      state.currentView = "full";
      fetchFullRankings();
    } else {
      fetchTop7();
    }
    fetchUserProgress();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
