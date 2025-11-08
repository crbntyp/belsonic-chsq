const livereload = require('livereload');
const path = require('path');

const server = livereload.createServer({
  delay: 100,
  exts: ['html', 'php', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg'],
  debug: false
});

const distPath = path.join(__dirname, 'dist');
console.log(`LiveReload watching: ${distPath}`);
server.watch(distPath);
