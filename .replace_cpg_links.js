const fs = require('fs');
const path = require('path');
const root = process.cwd();
const needle1 = 'href="cpg.php"';
const needle2 = "href='cpg.php'";
const repl1 = 'href="<?= isset($DEPT_ID) ? \'cpg.php?id=\' . (int)$DEPT_ID : \'cpg.php\' ?>"';
const repl2 = "href='<?= isset($DEPT_ID) ? \'cpg.php?id=\' . (int)$DEPT_ID : \'cpg.php\' ?>'";
const changed = [];
function walk(dir) {
  for (const name of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, name.name);
    if (name.isDirectory()) {
      walk(full);
    } else if (name.isFile() && full.endsWith('.php')) {
      let text = fs.readFileSync(full, 'utf8');
      const newText = text.split(needle1).join(repl1).split(needle2).join(repl2);
      if (newText !== text) {
        fs.writeFileSync(full, newText, 'utf8');
        changed.push(full);
      }
    }
  }
}
walk(root);
console.log('updated', changed.length, 'files');
changed.forEach(f => console.log(f));
