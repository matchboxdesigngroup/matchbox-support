---
name: detail-asana-task
description: Write and refine detailed Asana task descriptions for software bugs and feature work. Use this skill whenever Andrew mentions an Asana task, wants to write or improve a task description, needs to add acceptance criteria, or shares rough notes about a bug or feature. Triggers include: "detail this task", "write a description for", "help me write an Asana task", "add acceptance criteria", "refine this task", or any time raw bug reproduction steps or developer notes are shared and need to be turned into a structured task.
---

# Detail Asana Task

Turn rough notes, bug reproduction steps, or developer observations into a complete, well-structured Asana task description with acceptance criteria.

## Output structure

Always produce the following sections in order. Use `---` horizontal rules to separate sections. Use `**bold**` for section headers and numbered/lettered sub-items where appropriate.

### 1. Task title (if not provided or needs improvement)
A concise, specific title in the form:
- Bug: `[Component] [symptom]` — e.g. "Bug: Tissue report defaults to first grower farm for missing farm on import"
- Feature/refactor: plain imperative — e.g. "Refactor tissue report language to use user meta"
### 2. Summary
2–4 sentences explaining what the bug or feature is, why it matters, and (for bugs) what the root cause appears to be. Should be readable by a non-developer.

### 3. Steps to reproduce (bugs only)
Numbered steps. Each step should:
- Be a single, concrete action
- Clarify which surface (frontend/backend/API) the action happens on
- State what is observed after the action where relevant
### 4. Expected behavior
What should happen. Keep it brief — 2–4 bullet points or sentences.

### 5. Observed behavior
What actually happens. Mirror the structure of Expected behavior for easy comparison. Call out any frontend/backend inconsistencies explicitly.

### 6. Next steps
Numbered list of investigation or implementation tasks. Be specific — avoid vague items like "fix the bug". Each item should describe a concrete change.

### 7. Acceptance criteria
Numbered list. Each criterion must:
- Have a **bold label** summarizing what is being tested
- Be written as a condition: "When X, then Y" or "Z is true"
- Cover: the primary fix, edge cases, no-regression cases, and any cleanup/migration tasks implied by the fix
## Writing principles

- Distinguish frontend vs. backend behavior clearly when they differ — this is often the core of the bug
- Don't just restate the steps in the acceptance criteria — each criterion should be independently testable
- Add an "expected behavior" criterion for the happy path, not just the fix
- Flag out-of-scope items (e.g. data migration for historical records) with a note like "Confirm with team whether this is in scope"
- Use plain language — avoid jargon unless it's already in the user's notes
- Keep summaries non-technical enough for a PM to read
## Example shape

```
**Bug: [Title]**

**Summary**
[2–4 sentences]

---

**Steps to reproduce**
1. ...
2. ...

---

**Expected behavior**
- ...

**Observed behavior**
- ...

---

**Next steps**
1. ...

---

**Acceptance criteria**
1. **[Label]** When ... then ...
2. **[Label]** ...
```

## Tips

- If the user provides only next steps (no reproduction steps), write the summary and acceptance criteria from those and ask if reproduction steps are known.
- If root cause is identified in the notes (e.g. "saving to WP option instead of user meta"), surface it in the Summary and make sure the acceptance criteria cover both the fix and any cleanup.
- After producing the description, briefly note any criteria that may need team confirmation before being marked done.
 