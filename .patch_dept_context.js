const fs = require('fs');
const path = require('path');
const root = process.cwd();
const linkTargets = [
  'executives.php', 'ward_heads.php', 'personnel_gallery.php',
  'org_structure.php', 'risk_management.php', 'nursing_ethics.php',
  'dataset.php', 'kpi.php', 'service_profile.php', 'cpg.php', 'wi.php', 'research.php',
  'meeting_reports.php',
  'staffing.php', 'workload.php',
  'downloads.php', 'infection_control.php', 'knowledge_base.php',
  'patient_safety.php', 'plans_projects.php', 'regulations.php',
  'staff_dev_plan.php', 'training.php', 'vision_mission.php'
];
const changed = [];
function makeHrefReplacement(file) {
  const quote = file.includes("'") ? "'" : '"';
  const open = file.startsWith("'") ? "href='" : 'href="';
}
function walk(dir) {
  for (const dirent of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, dirent.name);
    if (dirent.isDirectory()) {
      walk(full);
    } else if (dirent.isFile() && full.endsWith('.php')) {
      let text = fs.readFileSync(full, 'utf8');
      let newText = text;

      // Replace hardcoded DEPT_ID defaults with GET-based selection.
      newText = newText.replace(/\$DEPT_ID\s*=\s*1;/g, '$DEPT_ID = isset($_GET[\'id\']) ? (int)$_GET[\'id\'] : 1;');

      // Replace department menu links to preserve current department.
      for (const target of linkTargets) {
        const tmpl = `<?= isset($DEPT_ID) ? '${target}?id=' . (int)$DEPT_ID : '${target}' ?>`;
        newText = newText.split(`href="${target}"`).join(`href="${tmpl}"`);
        newText = newText.split(`href='${target}'`).join(`href='${tmpl}'`);
      }

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
