export default {
  async fetch(request) {
    const url = new URL(request.url);
    if (url.pathname === '/blogs' || url.pathname.startsWith('/blog/')) {
      const target = "https://nurio.sh/proxy/clinton-agburum-78269853" + url.pathname;
      return fetch(target, { headers: request.headers });
    }
    return fetch(request);
  }
}
