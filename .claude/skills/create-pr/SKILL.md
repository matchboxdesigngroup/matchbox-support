---
name: create-pull-request
description: Create a GitHub pull request that follows Matchbox's standard PR template, with a non-technical summary, pre-submit checklist, testing steps, functional tests, code review section, and merging section. Use this skill whenever Andrew opens a PR, asks to draft a PR description, says "write the PR", "open a pull request", "fill out the PR template", or has changes ready to push and needs the PR body. Trigger even when he doesn't mention the template explicitly — any request to create or describe a pull request for a Matchbox repo should use this format.
---

# Create Pull Request

Draft a pull request body that conforms to the Matchbox Design Group PR template. Gather context first, fill the template, then create the PR (with confirmation) or hand Andrew the body to paste.

## Workflow

1. **Gather context.** Determine the changes from the conversation, the diff, or by asking. You need:
   - What changed and why (for the summary).
   - The Asana task link and/or GitHub issue number this closes.
   - How to test the changes (deploy target, navigation steps, setup).
   - The functional tests a reviewer should verify.
   - The target branch (default `main`).

   If a git repo is available, run `git diff main...HEAD --stat` and `git log main..HEAD --oneline` to infer scope. Read the actual diff for the summary rather than guessing.

2. **Write a non-technical summary.** The "Changes proposed" section must read clearly to a non-developer. Pick the verb that fits (adds / fixes / refactors / updates) and remove the unused starter lines. Always end with the `Closes` line pointing at the Asana task or GitHub issue.

3. **Fill the testing section.** Replace the placeholder navigation steps with concrete, numbered steps. Start from deploying the branch to `dev` unless told otherwise. Write functional tests as specific, checkable assertions ("As an admin, I can…", "It does not…").

4. **Leave checklists unchecked.** Author, functional review, code review, and merging checkboxes stay `[ ]` — they are verified by the people doing each step, not pre-filled. Do not check boxes on Andrew's behalf.

5. **Produce the body**, then create the PR or hand it off (see Output).

## Output

Fill the template below verbatim, substituting the bracketed/placeholder content. Keep the HTML comments out of the final body (they're authoring hints).

```markdown
## Changes proposed in this pull request
This PR [adds/fixes/refactors/updates] ...

Closes [Asana task link or GitHub issue #]

## Pre-submit checklist
As the author of this pull request, I verify that:
- [ ] I have set the target branch to `main`.
- [ ] I have detailed the purpose of this Pull Request in a non-technical way.
- [ ] I have detailed how to test the changes in the Pull Request.
- [ ] I have detailed the functional tests required for approval.
- [ ] I have performed a self-review of my code to ensure it is DRY and follows the team's coding standards.
- [ ] I have commented my code, particularly in hard-to-understand areas.
- [ ] I have made corresponding changes to the documentation.
- [ ] I have verified that my code does not introduce a debug warning in my local environment.
- [ ] I have verified that the functional tests work in my local environment.
- [ ] I have verified that all automated tests pass or have provided a detailed comment about why I am submitting with a failed pipeline. (Leave unchecked if the repository does not include automated tests.)
- [ ] I have verified that any dependent changes have been merged and published in downstream commits.
- [ ] I have added a link to this Pull Request in the Asana task.
- [ ] I have moved the Asana task to "Ready for Functional Review".
- [ ] I have left a comment in Asana and GitHub tagging a team member with a request for review.

## Testing
### How to test the changes in this pull request
Follow the steps below to test the changes in this PR.
1. Deploy this branch to the `dev` environment.
2. Navigate to ...
3. ...

### Functional tests
As the functional tester for this pull request, I verify that:
- [ ] It [does something]
- [ ] It does not ...
- [ ] As an admin, I can ...
- [ ] As a user, I can ...

Once testing is complete, notify the author of any failed tests and move the task to "Kick back" in Asana. If all tests pass, move the task to "Ready for Code Review" in Asana and tag a team member for code review.

### Code review
As the code reviewer for this pull request, I verify that:
- [ ] All automated tests have passed.
- [ ] The code is written (or documented) in a way that is easy to understand.
- [ ] The code is free of obvious errors and duplication.
- [ ] The code follows our coding standards.
- [ ] The code is sanitized or escaped appropriately for any SQL or XSS injection possibilities.

Once testing is complete, notify the author of any failed tests and move the task to "Kick back" in Asana or continue with the "merging" steps below.

## Merging
As the individual merging this pull request, I verify that:
- [ ] All automated tests have passed.
- [ ] All functional tests have passed.
- [ ] All code review tests have passed.
- [ ] I have moved the task to "Ready to Deploy" in Asana and notified the pull request author.
```

### Creating the PR

If the GitHub CLI is available and Andrew wants the PR opened directly:

```bash
gh pr create --base main --title "<title>" --body-file <path-to-body.md>
```

Creating a PR publishes content, so confirm the title, target branch, and body with Andrew before running `gh pr create`. If he only wants the text, output the filled body for him to paste and skip the CLI step.

## Notes

- Default target branch is always `main`.
- Remove the unused "This PR adds.../fixes.../refactors.../updates..." starter lines — keep only the one that applies.
- The `Closes` reference is required; prompt for it if missing.
- Never pre-check checklist items — each section is signed off by whoever performs that role.
