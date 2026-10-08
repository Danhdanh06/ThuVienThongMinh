(() => {
  const page = document.querySelector('.libinfo-page');
  if (!page) return;

  page.querySelectorAll('[data-count]').forEach(node => {
    const end = Number(node.dataset.count || 0);
    const startAt = performance.now();
    const duration = 700;
    const frame = now => {
      const p = Math.min(1, (now - startAt) / duration);
      const value = Math.round(end * (1 - Math.pow(1 - p, 3)));
      node.textContent = value.toLocaleString('vi-VN');
      if (p < 1) requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  });

  page.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const value = btn.dataset.copy || '';
      if (!value) return;
      try {
        await navigator.clipboard.writeText(value);
        const old = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép';
        btn.classList.add('copied');
        setTimeout(() => { btn.innerHTML = old; btn.classList.remove('copied'); }, 1500);
      } catch (_) {
        const input = document.createElement('textarea');
        input.value = value; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove();
      }
    });
  });

  const revealNodes = [...page.querySelectorAll('.libinfo-reveal')];
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
      });
    }, { threshold: .08 });
    revealNodes.forEach(node => observer.observe(node));
  } else revealNodes.forEach(node => node.classList.add('is-visible'));
})();
