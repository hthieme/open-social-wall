(function () {
  'use strict';

  function viewportGutter() {
    const width = document.documentElement.clientWidth || window.innerWidth;
    if (width <= 620) return 10;
    if (width <= 900) return 12;
    return 48;
  }

  function fitWallToViewport(wall) {
    const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
    const styles = window.getComputedStyle(wall);
    const maxWidth = parseFloat(styles.getPropertyValue('--osw-max-width')) || 1680;
    const gutter = viewportGutter();
    const targetWidth = Math.max(0, Math.min(maxWidth, viewportWidth - (gutter * 2)));

    wall.style.left = 'auto';
    wall.style.transform = 'none';
    wall.style.width = targetWidth + 'px';

    const rect = wall.getBoundingClientRect();
    const targetLeft = Math.max(gutter, (viewportWidth - targetWidth) / 2);
    const offset = targetLeft - rect.left;
    wall.style.transform = 'translateX(' + offset + 'px)';
  }

  function resizeCard(card, grid) {
    if (window.matchMedia('(max-width: 620px)').matches) {
      card.style.gridRowEnd = '';
      return;
    }

    const styles = window.getComputedStyle(grid);
    const row = parseFloat(styles.getPropertyValue('grid-auto-rows')) || 8;
    const gap = parseFloat(styles.getPropertyValue('row-gap')) || 0;
    const height = card.getBoundingClientRect().height;
    const span = Math.ceil((height + gap) / (row + gap));
    card.style.gridRowEnd = 'span ' + span;
  }

  function isVisible(card) {
    return !card.classList.contains('is-paged-hidden') && !card.classList.contains('is-filter-hidden');
  }

  function layout(grid) {
    grid.querySelectorAll('.osw-card').forEach(function (card) {
      if (isVisible(card)) {
        resizeCard(card, grid);
      } else {
        card.style.gridRowEnd = '';
      }
    });
  }

  function initWall(wall) {
    const grid = wall.querySelector('[data-osw-grid]');
    if (!grid) return;

    let currentFilter = 'all';
    const observer = new ResizeObserver(function () { relayout(); });
    const relayout = function () {
      window.requestAnimationFrame(function () {
        fitWallToViewport(wall);
        layout(grid);
      });
    };

    function bindCard(card) {
      if (card.dataset.oswBound === '1') return;
      card.dataset.oswBound = '1';

      card.addEventListener('click', function (event) {
        if (event.target.closest('a, button')) return;
        const url = card.dataset.url;
        if (url) window.open(url, '_blank', 'noopener,noreferrer');
      });

      card.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a, button')) {
          event.preventDefault();
          const url = card.dataset.url;
          if (url) window.open(url, '_blank', 'noopener,noreferrer');
        }
      });

      card.querySelectorAll('img').forEach(function (img) {
        if (!img.complete) img.addEventListener('load', relayout, { once: true });
      });

      observer.observe(card);
    }

    const cards = Array.from(wall.querySelectorAll('.osw-card'));
    cards.forEach(bindCard);

    function applyFilter() {
      cards.forEach(function (card) {
        const matches = currentFilter === 'all' || card.dataset.network === currentFilter;
        card.classList.toggle('is-filter-hidden', !matches);
      });
      relayout();
    }

    wall.querySelectorAll('[data-filter]').forEach(function (button) {
      button.addEventListener('click', function () {
        currentFilter = button.dataset.filter;
        wall.querySelectorAll('[data-filter]').forEach(function (b) {
          b.classList.toggle('is-active', b === button);
        });
        applyFilter();
      });
    });

    const loadButton = wall.querySelector('[data-osw-load-more]');
    const loadWrap = wall.querySelector('[data-osw-load-more-wrap]');
    const loadStatus = wall.querySelector('[data-osw-load-more-status]');
    const batch = Math.max(1, parseInt(grid.dataset.batchSize || (window.OSW_DATA && OSW_DATA.batchSize) || '12', 10));

    function remainingPagedCards() {
      return cards.filter(function (card) { return card.classList.contains('is-paged-hidden'); });
    }

    function updateLoadMoreState() {
      if (!loadButton || !loadWrap) return;
      const remaining = remainingPagedCards().length;
      loadWrap.classList.toggle('is-hidden', remaining === 0);
      if (remaining > 0) {
        loadButton.disabled = false;
        loadButton.textContent = (window.OSW_DATA && OSW_DATA.labels && OSW_DATA.labels.more) || 'Load more';
      }
      if (loadStatus) loadStatus.textContent = '';
    }

    if (loadButton && loadWrap) {
      loadButton.addEventListener('click', function () {
        const remaining = remainingPagedCards();
        if (!remaining.length) {
          updateLoadMoreState();
          return;
        }

        loadButton.disabled = true;
        loadButton.textContent = (window.OSW_DATA && OSW_DATA.labels && OSW_DATA.labels.loading) || 'Loading…';

        remaining.slice(0, batch).forEach(function (card) {
          card.classList.remove('is-paged-hidden');
          card.classList.add('osw-card--revealed');
          window.setTimeout(function () { card.classList.remove('osw-card--revealed'); }, 260);
        });

        applyFilter();

        window.setTimeout(function () {
          updateLoadMoreState();
          relayout();
        }, 120);
      });
    }

    observer.observe(grid);
    window.addEventListener('load', relayout, { once: true });
    window.addEventListener('resize', relayout, { passive: true });
    applyFilter();
    updateLoadMoreState();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-osw-wall]').forEach(initWall);
  });
})();
