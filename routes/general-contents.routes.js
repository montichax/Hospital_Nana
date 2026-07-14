const express = require("express");
const router = express.Router();
const db = require("../config/db");

// GET เนื้อหา "ทั่วไป" (ไม่ผูกกับแผนก) ของหมวดที่ระบุ — ใช้กับหน้าเว็บกลางขององค์กร เช่น vision_mission.php, kpi.php
router.get("/:section", async (req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM department_contents WHERE department_id IS NULL AND section = ? ORDER BY sort_order, id",
      [req.params.section]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
