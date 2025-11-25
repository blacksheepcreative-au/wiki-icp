import '@awesome.me/kit-977ae106ed';

// Placeholder for future enhancements (search widgets, AI helpers, etc.)
document.addEventListener('DOMContentLoaded', () => {
  document.body.classList.add('wiki-icp-ready');

  const normalizeFaIcons = () => {
    document.querySelectorAll('.svg-inline--fa').forEach((svg) => {
      svg.style.removeProperty('width');
      svg.style.removeProperty('height');
    });
  };

  normalizeFaIcons();

  if (window.MutationObserver) {
    const faObserver = new MutationObserver(normalizeFaIcons);
    faObserver.observe(document.body, { childList: true, subtree: true });
  }

  const tutorialApp = document.querySelector('[data-tutorial-app]');
  if (!tutorialApp) {
    return;
  }
  const postType = tutorialApp.dataset.postType || 'tutorial_video';

  const restRoot =
    (window.wikiIcpData && window.wikiIcpData.restUrl) ||
    (window.wpApiSettings && window.wpApiSettings.root) ||
    '/wp-json/';

  const sidebar = tutorialApp.querySelector('[data-tutorial-sidebar]');
  const sidebarToggle = tutorialApp.querySelector('[data-tutorial-sidebar-toggle]');
  const sidebarClose = tutorialApp.querySelector('[data-tutorial-sidebar-close]');
  const videoTarget = tutorialApp.querySelector('[data-tutorial-video]');
  const contentTarget = tutorialApp.querySelector('[data-tutorial-content]');
  const durationTarget = tutorialApp.querySelector('[data-tutorial-duration]');
  const durationWrapper = tutorialApp.querySelector('[data-tutorial-duration-wrapper]');
  const loader = tutorialApp.querySelector('[data-tutorial-loader]');
  const triggers = tutorialApp.querySelectorAll('[data-tutorial-trigger]');
  const catToggles = tutorialApp.querySelectorAll('[data-tutorial-cat-toggle]');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  if (sidebarClose && sidebar) {
    sidebarClose.addEventListener('click', () => {
      sidebar.classList.remove('open');
    });
  }

  catToggles.forEach((btn) => {
    btn.addEventListener('click', () => {
      const category = btn.closest('.tutorial-category');
      if (!category) {
        return;
      }

      const list = category.querySelector('.tutorial-items');
      const isOpen = category.classList.contains('is-open');

      const allCategories = tutorialApp.querySelectorAll('.tutorial-category');
      allCategories.forEach((cat) => {
        cat.classList.remove('is-open');
        const catList = cat.querySelector('.tutorial-items');
        if (catList) {
          catList.style.display = 'none';
        }
      });

      if (!isOpen) {
        category.classList.add('is-open');
        if (list) {
          list.style.display = '';
        }
      }
    });
  });

  let activeId = tutorialApp.dataset.defaultId || '';

  const setActive = (id) => {
    triggers.forEach((trigger) => {
      trigger.classList.toggle('is-active', trigger.dataset.tutorialTrigger === id);
    });
  };

  const closeSidebarOnMobile = () => {
    if (window.innerWidth < 1024 && sidebar?.classList.contains('open')) {
      sidebar.classList.remove('open');
    }
  };

  const fetchTutorial = (id) => {
    if (!id || !videoTarget || !contentTarget) {
      return;
    }

    loader?.classList.remove('hidden');
    const endpoint = `${restRoot.replace(/\/$/, '')}/wp/v2/${postType}/${id}`;

    fetch(endpoint)
      .then((response) => {
        if (!response.ok) {
          throw new Error('Network error');
        }
        return response.json();
      })
      .then((data) => {
        if (!data) {
          return;
        }

        const meta = data.meta || {};
        let content = data.content?.rendered || '';
        let embed = data.video_embed || meta.youtube_video || '';

        if (!embed) {
          const iframeMatch = content.match(/<iframe[\s\S]*?<\/iframe>/i);
          if (iframeMatch) {
            embed = iframeMatch[0];
            content = content.replace(iframeMatch[0], '');
          }
        }

        if (embed) {
          videoTarget.innerHTML = embed;
        } else {
          videoTarget.innerHTML = '';
        }

        contentTarget.innerHTML = content || '<p>No content available.</p>';

        if (durationTarget && durationWrapper) {
          const duration = meta.video_time || '';
          durationTarget.textContent = duration;
          if (duration) {
            durationWrapper.classList.remove('hidden');
          } else {
            durationWrapper.classList.add('hidden');
          }
        }

        closeSidebarOnMobile();
      })
      .catch(() => {
        contentTarget.innerHTML = '<p>Unable to load this tutorial. Please try again.</p>';
      })
      .finally(() => {
        loader?.classList.add('hidden');
      });
  };

  triggers.forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const id = trigger.dataset.tutorialTrigger;
      if (!id || id === activeId) {
        return;
      }
      activeId = id;
      setActive(id);
      fetchTutorial(id);
    });
  });

  if (activeId) {
    setActive(activeId);
    fetchTutorial(activeId);
  } else {
    loader?.classList.add('hidden');
  }
});

const initHeader = () => {
  const header = document.querySelector('[data-site-header]');
  if (!header) {
    return;
  }

  const navToggles = Array.from(header.querySelectorAll('[data-nav-toggle]'));
  const searchToggles = Array.from(header.querySelectorAll('[data-search-toggle]'));
  const searchPanel = header.querySelector('[data-search-panel]');
  const navClosers = header.querySelectorAll('[data-nav-close]');
  const desktopDropdownLinks = Array.from(
    header.querySelectorAll('.primary-nav-desktop .menu > li.menu-item-has-children > a')
  );

  const setExpanded = (elements, isOpen) => {
    elements.forEach((el) => el.setAttribute('aria-expanded', isOpen ? 'true' : 'false'));
  };

  const closeDesktopDropdowns = () => {
    header
      .querySelectorAll('.primary-nav-desktop .menu li.is-open')
      .forEach((item) => item.classList.remove('is-open'));
  };

  const setSearchPanelState = (isOpen) => {
    if (!searchPanel) {
      return;
    }
    searchPanel.style.maxHeight = isOpen ? `${searchPanel.scrollHeight}px` : '0px';
  };

  const closeNav = () => {
    header.classList.remove('is-nav-open');
    setExpanded(navToggles, false);
  };

  const closeSearch = () => {
    header.classList.remove('is-search-open');
    setExpanded(searchToggles, false);
    setSearchPanelState(false);
  };

  navToggles.forEach((btn) => {
    btn.addEventListener('click', () => {
      const willOpen = !header.classList.contains('is-nav-open');
      if (willOpen) {
        closeSearch();
        header.classList.add('is-nav-open');
        setExpanded(navToggles, true);
      } else {
        closeNav();
      }
    });
  });

  navClosers.forEach((btn) => {
    btn.addEventListener('click', closeNav);
  });

  desktopDropdownLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      if (window.innerWidth < 1024) {
        return;
      }

      const parent = link.parentElement;
      const href = link.getAttribute('href') || '';
      const isDummyLink = href === '#' || href === '';

      if (!parent.classList.contains('is-open')) {
        event.preventDefault();
        closeDesktopDropdowns();
        parent.classList.add('is-open');
        link.focus();
      } else if (isDummyLink) {
        event.preventDefault();
        parent.classList.remove('is-open');
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (window.innerWidth < 1024) {
      return;
    }

    if (!event.target.closest('.primary-nav-desktop')) {
      closeDesktopDropdowns();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeDesktopDropdowns();
    }
  });

  searchToggles.forEach((btn) => {
    btn.addEventListener('click', () => {
      const willOpen = !header.classList.contains('is-search-open');
      if (willOpen) {
        closeNav();
        header.classList.add('is-search-open');
        setExpanded(searchToggles, true);
        setSearchPanelState(true);
        const input = searchPanel?.querySelector('input[type="search"]');
        if (input) {
          setTimeout(() => input.focus(), 150);
        }
      } else {
        closeSearch();
      }
    });
  });

  const handleResize = () => {
    if (window.innerWidth >= 1024) {
      header.classList.remove('is-nav-open', 'is-search-open');
      setExpanded(navToggles, false);
      setExpanded(searchToggles, false);
      if (searchPanel) {
        searchPanel.style.maxHeight = '';
      }
      closeDesktopDropdowns();
    } else {
      if (!header.classList.contains('is-search-open')) {
        setSearchPanelState(false);
      }
      closeDesktopDropdowns();
    }
  };

  window.addEventListener('resize', handleResize);
  handleResize();
};

const initSearchApp = () => {
  const app = document.querySelector('[data-search-app]');
  if (!app) {
    return;
  }

  const restRoot =
    (window.wikiIcpData && window.wikiIcpData.restUrl) ||
    (window.wpApiSettings && window.wpApiSettings.root) ||
    '/wp-json/';

  const resultsList = app.querySelector('[data-search-results]');
  const resultsCount = app.querySelector('[data-results-count]');
  const resultsMeta = app.querySelector('[data-results-meta]');
  const aiAnswer = app.querySelector('[data-ai-answer]');
  const aiStatus = app.querySelector('[data-ai-status]');
  const aiActions = app.querySelector('[data-ai-actions]');
  const aiCard = app.querySelector('.ai-card');
  const searchForm = app.querySelector('[data-search-form]');
  const searchInput = app.querySelector('[data-search-input]');
  const portalFilters = app.querySelector('[data-portal-filters]');
  const typeCheckboxes = app.querySelectorAll('input[name="search-type"]');

  const portals = JSON.parse(app.dataset.portals || '[]');

  const state = {
    query: app.dataset.searchQuery || '',
    portal: null,
    types: new Set(['articles', 'tutorials']),
  };

  const AI_COPY = {
    intro: 'Enter a question or search above to see an AI-generated answer powered by your wiki.',
    empty: 'No AI response available for this query. Try refining your search.',
    error: 'Unable to generate an AI answer right now.',
    disabled: 'AI preview is currently disabled.',
  };

  const TYPE_ICONS = {
    article: '<i class="fa-light fa-book-open-cover" aria-hidden="true"></i>',
    tutorial: '<i class="fa-light fa-circle-play" aria-hidden="true"></i>',
    installation: '<i class="fa-light fa-screwdriver-wrench" aria-hidden="true"></i>',
  };
  const DEFAULT_TYPE_ICON = '<i class="fa-light fa-file-lines" aria-hidden="true"></i>';

  const PORTAL_ICONS = {
    'ordering-portal': '<i class="fa-light fa-cart-shopping" aria-hidden="true"></i>',
    'quoting-portal': '<i class="fa-light fa-comments" aria-hidden="true"></i>',
    'licensee-portal': '<i class="fa-light fa-file-contract" aria-hidden="true"></i>',
  };
  const DEFAULT_PORTAL_ICON = '<i class="fa-light fa-compass" aria-hidden="true"></i>';

  const ICON_SHORTCODE_REGEX = /\[icon\s+([^\]]+)\]/gi;
  const ICON_ATTR_REGEX = /(\w+)="([^"]*)"/gi;

  const INSTALL_REGEX = /(install|installation|replace|mount|setup|fit|fasten|drill)/i;
  const hasInstallIntent = () => INSTALL_REGEX.test(state.query || '');

  const getResultRank = (item) => {
    if (hasInstallIntent()) {
      if (item?.subtype === 'installation') {
        return 0;
      }
      if (item?.type === 'tutorial') {
        return 1;
      }
      return 2;
    }

    if (item?.type === 'article') {
      return 0;
    }
    if (item?.subtype === 'installation') {
      return 1;
    }
    if (item?.type === 'tutorial') {
      return 2;
    }
    return 3;
  };

  const renderPortalFilters = () => {
    if (!portalFilters) {
      return;
    }
    portalFilters.innerHTML = '';

    const createOption = (label, slug) => {
      const id = `portal-${slug || 'all'}`;
      const wrapper = document.createElement('label');
      wrapper.setAttribute('for', id);
      wrapper.innerHTML = `
        <input type="radio" id="${id}" name="portal-filter" value="${slug}" ${(!slug && !state.portal) || (state.portal?.slug === slug) ? 'checked' : ''}>
        <span>${label}</span>
      `;
      portalFilters.appendChild(wrapper);
    };

    createOption('All Portals', '');
    portals.forEach((portal) => {
      createOption(portal.name, portal.slug);
    });

    portalFilters.querySelectorAll('input[name="portal-filter"]').forEach((input) => {
      input.addEventListener('change', (event) => {
        const slug = event.target.value || '';
        state.portal = slug ? portals.find((portal) => portal.slug === slug) || null : null;
        performSearch();
      });
    });
  };

  const parseIconShortcode = (attrsText) => {
    const attrs = {};
    attrsText.replace(ICON_ATTR_REGEX, (_, key, value) => {
      attrs[key.toLowerCase()] = value;
      return '';
    });
    const name = attrs.name || '';
    if (!name) {
      return '';
    }
    const prefix = attrs.prefix || 'fa';
    const classes = `${prefix} fa-${name}`.trim();
    return `<i class="${classes}" aria-hidden="true"></i>`;
  };

  const enhanceExcerpt = (text) => {
    if (!text) {
      return '';
    }
    return text.replace(ICON_SHORTCODE_REGEX, (_, attrs) => parseIconShortcode(attrs) || '');
  };

  const appendLinkifiedText = (container, text) => {
    const urlRegex = /(https?:\/\/[^\s)]+)/g;
    let lastIndex = 0;
    let match;
    while ((match = urlRegex.exec(text)) !== null) {
      const matchIndex = match.index;
      if (matchIndex > lastIndex) {
        container.appendChild(document.createTextNode(text.slice(lastIndex, matchIndex)));
      }
      let url = match[1];
      let trailing = '';
      const trailingMatch = url.match(/([).,!?:]+)$/);
      if (trailingMatch) {
        trailing = trailingMatch[1];
        url = url.slice(0, -trailing.length);
      }
      const link = document.createElement('a');
      link.href = url;
      link.textContent = url.replace(/^https?:\/\//, '');
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      container.appendChild(link);
      if (trailing) {
        container.appendChild(document.createTextNode(trailing));
      }
      lastIndex = matchIndex + match[0].length;
    }
    if (lastIndex < text.length) {
      container.appendChild(document.createTextNode(text.slice(lastIndex)));
    }
  };

  const setAiState = (state) => {
    if (!aiCard) {
      return;
    }
    aiCard.classList.remove('ai-ready', 'ai-error');
    if (state === 'ready') {
      aiCard.classList.add('ai-ready');
    } else if (state === 'error') {
      aiCard.classList.add('ai-error');
    }
  };

  const setAiPlaceholder = (message) => {
    if (!aiAnswer) {
      return;
    }
    aiAnswer.innerHTML = '';
    const p = document.createElement('p');
    p.textContent = message;
    aiAnswer.appendChild(p);
  };

  const renderAiMessage = (message) => {
    if (!aiAnswer) {
      return false;
    }
    const text = typeof message === 'string' ? message.trim() : '';
    aiAnswer.innerHTML = '';
    if (!text) {
      return false;
    }
    const paragraphs = text.split(/\n+/).map((part) => part.trim()).filter(Boolean);
    if (!paragraphs.length) {
      return false;
    }
    paragraphs.forEach((part) => {
      const p = document.createElement('p');
      appendLinkifiedText(p, part);
      aiAnswer.appendChild(p);
    });
    return true;
  };

  const renderAiActions = (items) => {
    if (!aiActions) {
      return;
    }
    aiActions.innerHTML = '';
    if (!Array.isArray(items) || !items.length) {
      return;
    }
    const seen = new Set();
    const curated = [];
    items.forEach((item) => {
      if (!item?.title || !item?.link) {
        return;
      }
      const key = `${item.title}|${item.link}`;
      if (seen.has(key)) {
        return;
      }
      seen.add(key);
      curated.push(item);
    });

    const limited = curated.slice(0, 4);
    if (!limited.length) {
      return;
    }

    const label = document.createElement('p');
    label.className = 'ai-links-label';
    label.textContent = 'Useful links';
    aiActions.appendChild(label);

    limited.forEach((item) => {
      const actionType = item.subtype || item.type || 'article';
      const action = document.createElement('a');
      action.className = 'ai-action-link';
      action.href = item.link;
      action.target = '_self';
      action.setAttribute('data-type', actionType);
      const icon = document.createElement('span');
      icon.setAttribute('aria-hidden', 'true');
      icon.innerHTML = TYPE_ICONS[actionType] || DEFAULT_TYPE_ICON;
      const label = document.createElement('span');
      label.textContent = item.title;
      action.appendChild(icon);
      action.appendChild(label);
      aiActions.appendChild(action);
    });
  };

  const renderCards = (items) => {
    if (!resultsList) {
      return;
    }
    resultsList.innerHTML = '';
    if (!items.length) {
      resultsList.innerHTML = `<p class="muted">${state.query ? 'No results match your filters.' : 'Enter a search above to see results.'}</p>`;
      return;
    }

    items.forEach((item) => {
      const card = document.createElement('article');
      card.className = 'search-card';
      const typeIconKey = item.subtype || item.type;
      const typeIcon = TYPE_ICONS[typeIconKey] || DEFAULT_TYPE_ICON;
      const portalIcon = item.portal ? (PORTAL_ICONS[item.portalSlug] || DEFAULT_PORTAL_ICON) : '';
      const portalBadge = item.portal
        ? `<span class="portal-label">${portalIcon} ${item.portal}</span>`
        : '';
      const excerpt = enhanceExcerpt(item.excerpt || '');
      card.innerHTML = `
        <header>
          <span class="badge">${typeIcon} ${item.typeLabel}</span>
          ${portalBadge}
        </header>
        <div class="search-card-body">
          <h3><a href="${item.link}">${item.title}</a></h3>
          <p>${excerpt || ''}</p>
        </div>
        <footer>
          <span>${item.meta || ''}</span>
          <a class="button-link" href="${item.link}">${item.cta}</a>
        </footer>
      `;
      resultsList.appendChild(card);
    });
  };

  const updateCounts = (articlesCount, tutorialsCount) => {
    const total = articlesCount + tutorialsCount;
    if (resultsCount) {
      resultsCount.textContent = `${total} ${total === 1 ? 'result' : 'results'}`;
    }

    if (resultsMeta) {
      const parts = [];
      if (articlesCount) {
        parts.push(`${articlesCount} help topics`);
      }
      if (tutorialsCount) {
        parts.push(`${tutorialsCount} tutorials`);
      }
      resultsMeta.textContent = parts.join(' · ');
    }
  };

  const fetchSearchResults = () => {
    if (!state.query) {
      renderCards([]);
      updateCounts(0, 0);
      return Promise.resolve();
    }

    resultsList.innerHTML = '<p class="muted">Loading results…</p>';

    return fetch(`${restRoot.replace(/\/$/, '')}/wiki-icp/v1/search`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        query: state.query,
        portal: state.portal?.slug || '',
        types: Array.from(state.types),
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        const articleResults = Array.isArray(data?.articles) ? data.articles : [];
        const tutorialResults = Array.isArray(data?.tutorials) ? data.tutorials : [];
        updateCounts(articleResults.length, tutorialResults.length);

        const combined = [];
        if (state.types.has('articles')) {
          combined.push(...articleResults);
        }
        if (state.types.has('tutorials')) {
          combined.push(...tutorialResults);
        }
        combined.sort((a, b) => getResultRank(a) - getResultRank(b));
        renderCards(combined);
      })
      .catch(() => {
        resultsList.innerHTML = '<p class="muted">Unable to load results.</p>';
      });
  };

  const fetchAiSummary = () => {
    if (!aiAnswer || !aiStatus) {
      return Promise.resolve();
    }

    if (!state.query) {
      setAiState(null);
      setAiPlaceholder(AI_COPY.intro);
      aiStatus.textContent = '';
      renderAiActions([]);
      return Promise.resolve();
    }

    setAiState(null);
    aiStatus.textContent = 'Thinking…';
    setAiPlaceholder('Gathering an answer…');
    renderAiActions([]);

    return fetch(`${restRoot.replace(/\/$/, '')}/wiki-icp/v1/assist`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        query: state.query,
        portal: state.portal?.slug || '',
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        const status = data?.status || '';
        const suggestions = Array.isArray(data?.suggestions) ? data.suggestions : [];
        const message = typeof data?.message === 'string' ? data.message.trim() : '';
        renderAiActions(suggestions);

        if (message) {
          renderAiMessage(message);
          aiStatus.textContent = 'AI Preview';
          setAiState('ready');
          return;
        }

        if (status === 'disabled') {
          setAiPlaceholder(AI_COPY.disabled);
          aiStatus.textContent = '';
          setAiState(null);
          return;
        }

        if (status === 'error') {
          setAiPlaceholder(AI_COPY.error);
          aiStatus.textContent = 'Preview unavailable';
          setAiState('error');
          return;
        }

        setAiPlaceholder(AI_COPY.empty);
        aiStatus.textContent = '';
        setAiState(null);
      })
      .catch(() => {
        setAiPlaceholder(AI_COPY.error);
        aiStatus.textContent = 'Preview unavailable';
        setAiState('error');
      });
  };

  const performSearch = () => {
    if (!state.query) {
      renderCards([]);
      updateCounts(0, 0);
      setAiState(null);
      setAiPlaceholder(AI_COPY.intro);
      aiStatus.textContent = '';
      renderAiActions([]);
      return;
    }

    fetchAiSummary();
    fetchSearchResults();
    const params = new URLSearchParams(window.location.search);
    params.delete('s');
    if (state.query) {
      params.set('q', state.query);
    } else {
      params.delete('q');
    }
    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
  };

  if (searchForm && searchInput) {
    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      state.query = searchInput.value.trim();
      performSearch();
    });
  }

  typeCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      if (checkbox.checked) {
        state.types.add(checkbox.value);
      } else {
        state.types.delete(checkbox.value);
        if (state.types.size === 0) {
          state.types.add(checkbox.value);
          checkbox.checked = true;
          return;
        }
      }
      performSearch();
    });
  });

  renderPortalFilters();
  if (state.query) {
    performSearch();
  }
};

document.addEventListener('DOMContentLoaded', () => {
  initHeader();
  initSearchApp();
});
