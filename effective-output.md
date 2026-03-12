# Getting Maximum & Accurate Output from Claude Code

A practical guide for developers to master Claude Code prompting, context management, and advanced features.

---

## Table of Contents

1. [The Golden Rule](#1-the-golden-rule)
2. [Writing Better Prompts](#2-writing-better-prompts)
3. [Using CLAUDE.md (Project Context)](#3-using-claudemd-project-context)
4. [Memory System](#4-memory-system)
5. [Plan Mode](#5-plan-mode)
6. [Skills (Slash Commands)](#6-skills-slash-commands)
7. [Context Management](#7-context-management)
8. [Hooks (Automation)](#8-hooks-automation)
9. [MCP Servers (External Tools)](#9-mcp-servers-external-tools)
10. [Subagents (Parallel Research)](#10-subagents-parallel-research)
11. [Worktrees (Parallel Development)](#11-worktrees-parallel-development)
12. [Session Management](#12-session-management)
13. [Permission Modes](#13-permission-modes)
14. [Non-Interactive Mode (CI/Scripts)](#14-non-interactive-mode-ciscripts)
15. [Common Mistakes & How to Fix Them](#15-common-mistakes--how-to-fix-them)
16. [Prompt Templates for Laravel Projects](#16-prompt-templates-for-laravel-projects)
17. [Cheat Sheet](#17-cheat-sheet)

---

## 1. The Golden Rule

**Always tell Claude how to verify its work.**

Without verification, Claude produces code that *looks* correct but may not *run* correctly. You become the only feedback loop, wasting your time reviewing line by line.

### Bad Prompt

```
Write a function that validates email addresses.
```

### Good Prompt

```
Write a validateEmail function in app/Services/ValidationService.php.

Test cases:
- user@example.com → true
- invalid → false
- user@.com → false

Create a feature test, run `php artisan test --filter=ValidateEmail`, and fix any failures.
Then run `vendor/bin/pint --dirty` to format.
```

The difference: Claude writes the code, runs the tests, sees failures, and fixes them — all without you doing anything.

---

## 2. Writing Better Prompts

### 2.1 Be Specific, Not Vague

| Vague (Bad) | Specific (Good) |
|---|---|
| "fix the bug" | "the `/api/v1/licenses` endpoint returns 500 when the license key has special characters. Fix it." |
| "add tests" | "add a feature test for LicenseController@store covering: valid input, missing fields, duplicate key" |
| "refactor this" | "extract the build logic from ProcessBuild job into a BuildService class. Keep the job as a thin wrapper." |
| "make it better" | "reduce N+1 queries in ThemeController@index by eager loading components and plugins" |

### 2.2 The Prompt Structure

Use this structure for complex tasks:

```
[Context]: Brief background on what exists and why you need the change.

[Task]: Exactly what to do. Be precise about file paths, method names, behavior.

[Success Criteria]: How to verify. Tests to run, expected output, edge cases.

[Constraints]: Patterns to follow, files to reference, things to avoid.

[Examples]: Point to existing code that demonstrates the pattern.
```

### 2.3 Reference Existing Code

Instead of describing patterns in words, point Claude to real code:

```
Look at how ThemeController handles CRUD operations.
Follow the same pattern to create a PluginController with:
- index (paginated, with search)
- store (with FormRequest validation)
- show
- update
- destroy
```

You can also use `@file.php` syntax to include file contents directly in your prompt.

### 2.4 Describe Root Cause, Not Symptoms

```
# Bad — describes the symptom
"The page is slow"

# Good — describes the root cause
"ThemeController@index loads all components without pagination or eager loading,
causing 200+ queries on pages with many themes. Add pagination (15 per page)
and eager load the 'components' relationship."
```

### 2.5 One Task Per Prompt

Don't combine unrelated tasks in a single prompt. Each prompt should have one clear objective.

```
# Bad — two unrelated tasks
"Fix the license validation bug and also add a new theme export feature"

# Good — one task, clear scope
"Fix the license validation bug: when an expired license is checked,
LicenseService@validate should return LicenseResponseStatus::Expired
instead of throwing an exception."
```

If you have multiple tasks, use `/clear` between them.

---

## 3. Using CLAUDE.md (Project Context)

### 3.1 What is CLAUDE.md?

`CLAUDE.md` is a file at the root of your project that Claude reads **at the start of every session**. It tells Claude about your project's architecture, conventions, and rules.

Think of it as onboarding documentation — but for Claude, not humans.

### 3.2 What to Include

```markdown
# CLAUDE.md

## Commands
php artisan test              # run tests
vendor/bin/pint --dirty       # format code
npm run build                 # build frontend

## Architecture
- Controllers in app/Http/Controllers/Api/V1/
- Always use Form Requests for validation
- Services in app/Services/ for business logic
- API Resources for response transformation

## Rules
- Never use DB:: facade, always use Eloquent
- Never inline validation in controllers
- Always write feature tests for new endpoints
```

### 3.3 What NOT to Include

- Standard Laravel conventions (Claude already knows them)
- Information that changes frequently
- Long tutorials or explanations
- Anything Claude can figure out by reading code

### 3.4 Keep It Short

**Target: under 200 lines.** Every line consumes context tokens. Long CLAUDE.md files reduce Claude's adherence because important rules get buried.

### 3.5 Splitting Into Multiple Files

For large projects, split rules into topic-specific files:

```
.claude/
  rules/
    api.md          # API conventions
    testing.md      # Testing rules
    database.md     # Database patterns
```

Claude loads all `.claude/rules/*.md` files automatically.

### 3.6 Hierarchy

Claude loads CLAUDE.md files at multiple levels:

```
~/.claude/CLAUDE.md              # Global (all projects)
/project/CLAUDE.md               # Project root
/project/src/CLAUDE.md           # Directory-specific
/project/src/api/CLAUDE.md       # Deeper directory
```

Deeper files add specificity. Use this for monorepos or large projects.

---

## 4. Memory System

### 4.1 What is Memory?

Claude has a persistent memory directory at `~/.claude/projects/<project>/memory/`. Files here survive across sessions.

### 4.2 How to Use It

Tell Claude to remember something:

```
Remember that we always use UUIDs instead of auto-increment IDs for new models.
```

Claude saves this to its memory files and references it in future sessions.

### 4.3 What to Store in Memory

- Patterns confirmed across multiple sessions
- User preferences (coding style, workflow)
- Solutions to recurring problems
- Key architectural decisions

### 4.4 What NOT to Store

- Temporary task context
- Unverified information
- Anything already in CLAUDE.md

### 4.5 Correcting Memory

If Claude remembers something wrong, tell it:

```
That's incorrect. We switched from UUIDs to ULIDs last month. Update your memory.
```

Claude will find and fix the incorrect entry.

---

## 5. Plan Mode

### 5.1 What is Plan Mode?

Plan Mode lets Claude **explore and think** without making any changes. It reads files, analyzes code, and creates a step-by-step plan — but never edits anything.

### 5.2 When to Use Plan Mode

- Changes touch 3+ files
- You're unfamiliar with the affected code
- The task scope is unclear
- You want to understand impact before changing anything

### 5.3 When to Skip Plan Mode

- Small, clear tasks (fix a typo, rename a variable)
- You could describe the exact diff in one sentence

### 5.4 How to Use It

**Option 1: Toggle with keyboard**
Press `Shift+Tab` to switch between Plan Mode and Normal Mode.

**Option 2: Start in Plan Mode**
```bash
claude --permission-mode plan
```

**Option 3: Ask for a plan in your prompt**
```
Don't make any changes yet. First, analyze how our authentication system works
and create a plan for adding OAuth2 support. List every file that needs changes.
```

### 5.5 Workflow

```
1. Enter Plan Mode (Shift+Tab)
2. Ask Claude to explore and plan
3. Review the plan
4. Switch to Normal Mode (Shift+Tab again)
5. Ask Claude to implement the plan
```

---

## 6. Skills (Slash Commands)

### 6.1 What are Skills?

Skills are reusable workflows triggered by slash commands. They load specialized prompts that guide Claude through multi-step tasks.

### 6.2 Built-in Skills

| Skill | What It Does |
|---|---|
| `/simplify` | Review changed code for quality, reuse, efficiency |
| `/loop 5m /command` | Run a command repeatedly on an interval |
| `/claude-api` | Help building apps with the Claude API |
| `/keybindings-help` | Customize keyboard shortcuts |

### 6.3 Creating Custom Skills

Create a file at `.claude/skills/<skill-name>/SKILL.md`:

```markdown
---
name: add-endpoint
description: Add a new API endpoint following project patterns
disable-model-invocation: true
---

Add a new API endpoint: $ARGUMENTS

Follow these steps:
1. Check existing endpoints in routes/api_v1.php for patterns
2. Create controller in app/Http/Controllers/Api/V1/
3. Create Form Request in app/Http/Requests/
4. Create API Resource in app/Http/Resources/
5. Add route to routes/api_v1.php
6. Write feature test in tests/Feature/
7. Run tests: php artisan test --filter=<TestName>
8. Format: vendor/bin/pint --dirty
```

**Usage:**
```
/add-endpoint License
```

`$ARGUMENTS` is replaced with whatever you type after the skill name.

### 6.4 Skill Ideas for Laravel Projects

- `/add-endpoint` — create a full API endpoint (controller, request, resource, test)
- `/add-model` — create model with migration, factory, seeder
- `/add-service` — create a service class with tests
- `/review-security` — check for common vulnerabilities
- `/deploy-check` — verify everything is ready for deployment

---

## 7. Context Management

### 7.1 Why Context Matters

Claude has a fixed context window (like RAM). Everything consumes it:
- Your prompts
- File contents Claude reads
- Command outputs
- Claude's own responses

When context fills up, Claude's quality degrades. **Managing context is the most important skill.**

### 7.2 Monitor Context Usage

Set up a status line to see context usage in real-time:
```
/statusline
```

This shows context percentage used. When you approach 80%, take action.

### 7.3 Clear Between Tasks

```
/clear
```

This resets the entire conversation. CLAUDE.md and memory are reloaded, but everything else is gone. Use this between unrelated tasks.

### 7.4 Compact Instead of Clear

If you want to keep some context but free up space:

```
/compact "Focus on the API authentication changes"
```

Claude summarizes the conversation, keeping what's relevant to your focus area and discarding the rest.

### 7.5 Use Subagents for Research

Instead of having Claude read 20 files in your main context:

```
Use a subagent to explore how our build system works.
Summarize the key files and patterns.
```

Subagents have their own context window. They explore, then return a summary — keeping your main context clean.

### 7.6 The 2-Correction Rule

If you've corrected Claude twice on the same issue and it's still wrong:

1. **Stop correcting** — you're wasting context
2. Run `/clear`
3. Write a better initial prompt that prevents the mistake

---

## 8. Hooks (Automation)

### 8.1 What are Hooks?

Hooks are shell commands that run automatically when Claude performs certain actions. Unlike CLAUDE.md instructions (which Claude may or may not follow), hooks are **guaranteed to execute**.

### 8.2 Hook Events

| Event | When It Fires |
|---|---|
| `PreToolUse` | Before Claude uses a tool (can block it) |
| `PostToolUse` | After Claude uses a tool |
| `Notification` | When Claude sends a notification |
| `Stop` | When Claude finishes a response |

### 8.3 Example: Auto-Format on Every Edit

Add to `.claude/settings.json`:

```json
{
  "hooks": [
    {
      "name": "auto-format",
      "events": ["PostToolUse"],
      "matchers": [
        {
          "tools": ["Edit", "Write"]
        }
      ],
      "handlers": [
        {
          "command": "vendor/bin/pint --dirty"
        }
      ]
    }
  ]
}
```

Now every time Claude edits or creates a file, Pint runs automatically.

### 8.4 Example: Block Writes to Protected Files

```json
{
  "hooks": [
    {
      "name": "protect-env",
      "events": ["PreToolUse"],
      "matchers": [
        {
          "tools": ["Edit", "Write"],
          "paths": [".env", ".env.*"]
        }
      ],
      "handlers": [
        {
          "command": "echo 'BLOCKED: Do not modify .env files' && exit 1"
        }
      ]
    }
  ]
}
```

### 8.5 Configure Interactively

```
/hooks
```

This walks you through setting up hooks step by step.

---

## 9. MCP Servers (External Tools)

### 9.1 What are MCP Servers?

MCP (Model Context Protocol) servers give Claude access to external tools and data sources. They extend Claude's capabilities beyond file reading and shell commands.

### 9.2 Your Configured MCP Servers

| Server | What It Does |
|---|---|
| `laravel-boost` | Laravel-specific tools: search docs, run tinker, query database, list artisan commands |
| `ide` | Code intelligence from your IDE |

### 9.3 Using Laravel Boost

Ask Claude to use Boost tools explicitly:

```
Use search-docs to find Laravel 11 documentation on rate limiting.
```

```
Use the tinker tool to check how many active licenses exist.
```

```
Use database-query to show me the last 10 builds with their statuses.
```

### 9.4 Adding New MCP Servers

```bash
claude mcp add <server-name> -- <command>
```

Example — add a GitHub MCP server:
```bash
claude mcp add github -- npx @modelcontextprotocol/server-github
```

---

## 10. Subagents (Parallel Research)

### 10.1 What are Subagents?

Subagents are independent Claude instances that run in their own context window. They explore, research, and report back without cluttering your main conversation.

### 10.2 When to Use Subagents

- Exploring unfamiliar parts of the codebase
- Investigating a bug across multiple files
- Researching documentation
- Any task that requires reading many files

### 10.3 How to Request Subagents

```
Use subagents to:
1. Explore how our authentication middleware works
2. Find all places where LicenseService is used
3. Check if we have any existing rate limiting setup

Report back a summary of findings.
```

Claude launches multiple subagents in parallel, each handling one task.

### 10.4 Types of Subagents

| Type | Use Case |
|---|---|
| `Explore` | Fast codebase exploration (find files, search code) |
| `Plan` | Design implementation plans |
| `general-purpose` | Complex multi-step research |

---

## 11. Worktrees (Parallel Development)

### 11.1 What are Worktrees?

Worktrees create isolated copies of your repository. Each worktree has its own branch and working directory. Changes in one worktree don't affect another.

### 11.2 When to Use Worktrees

- Working on multiple features simultaneously
- Testing a risky change without affecting your main branch
- Comparing two different approaches

### 11.3 How to Use

```bash
# Start Claude in an isolated worktree
claude --worktree feature-oauth

# Start another session in a different worktree
claude --worktree bugfix-license-validation
```

When you exit, if no changes were made, the worktree is cleaned up automatically.

---

## 12. Session Management

### 12.1 Resume Previous Sessions

```bash
# Resume the most recent session
claude --continue

# Pick from a list of recent sessions
claude --resume

# Resume a specific session by name
claude --resume oauth-migration
```

### 12.2 Name Your Sessions

Inside a session:
```
/rename oauth-migration
```

### 12.3 When to Start Fresh vs Resume

**Start fresh** when:
- The previous session's context is mostly irrelevant
- You've changed your approach
- Context was nearly full

**Resume** when:
- You're continuing the exact same task
- You need Claude to remember decisions from the previous session

---

## 13. Permission Modes

### 13.1 Available Modes

| Mode | Behavior |
|---|---|
| `default` | Ask permission for risky actions |
| `plan` | Read-only, no changes allowed |
| `auto-accept` | Accept all actions automatically |

### 13.2 Allowlisting Safe Commands

Instead of approving every command, pre-approve safe ones in `.claude/settings.json`:

```json
{
  "permissions": {
    "allow": [
      "Bash(vendor/bin/pint:*)",
      "Bash(php artisan test:*)",
      "Bash(npm run:*)"
    ]
  }
}
```

Now Claude can run tests and format code without asking each time.

---

## 14. Non-Interactive Mode (CI/Scripts)

### 14.1 One-Off Commands

```bash
# Quick analysis
claude -p "list all API endpoints in routes/api_v1.php"

# Structured output for scripts
claude -p "list all models" --output-format json

# Pipe input
cat error.log | claude -p "analyze these errors and suggest fixes"
```

### 14.2 In CI/CD Pipelines

```bash
# Code review in CI
claude -p "review the diff for security issues" --output-format json

# Generate documentation
claude -p "generate API documentation for routes/api_v1.php"
```

---

## 15. Common Mistakes & How to Fix Them

### Mistake 1: The Kitchen Sink Session

**Problem:** You started with one task, added unrelated tasks, and now context is full of noise. Claude starts forgetting earlier instructions.

**Fix:** Use `/clear` between unrelated tasks. One task per session.

### Mistake 2: Correcting Endlessly

**Problem:** Claude does something wrong. You correct it. Still wrong. You correct again. Still wrong. Context is now full of failed attempts.

**Fix:** After 2 corrections, `/clear` and write a better initial prompt. The issue is usually in your prompt, not in Claude.

### Mistake 3: Over-Specified CLAUDE.md

**Problem:** CLAUDE.md is 500 lines. Claude ignores half the rules because important ones are buried.

**Fix:** Keep it under 200 lines. If Claude already does something correctly without being told, delete that rule.

### Mistake 4: No Verification

**Problem:** Claude produces code that looks correct but has subtle bugs. You only discover them later in production.

**Fix:** Always include test commands in your prompt. "Run the tests and fix failures" should be in every prompt.

### Mistake 5: Infinite Exploration

**Problem:** You asked Claude to "investigate the codebase" without scope. It reads 50 files, fills context, and produces a vague summary.

**Fix:** Scope narrowly: "Read LicenseService.php and ExternalLicenseProvider.php. Explain how license validation works."

### Mistake 6: Not Using Existing Patterns

**Problem:** You describe a pattern in words. Claude interprets it differently than you intended.

**Fix:** Point to existing code: "Follow the pattern in ThemeController.php" is better than describing the pattern.

---

## 16. Prompt Templates for Laravel Projects

### Create a New API Endpoint

```
Create a new API endpoint for managing [Resource].

Reference existing patterns:
- Route: routes/api_v1.php
- Controller: app/Http/Controllers/Api/V1/ThemeController.php
- Form Request: app/Http/Requests/ThemeRequest.php
- Resource: app/Http/Resources/ThemeResource.php

Implement:
1. Controller with index, store, show, update, destroy
2. Form Request with validation rules
3. API Resource for response formatting
4. Routes in routes/api_v1.php
5. Feature test in tests/Feature/

Run `php artisan test --filter=[Resource]Test` and fix failures.
Run `vendor/bin/pint --dirty` to format.
```

### Fix a Bug

```
Bug: [describe the exact bug with steps to reproduce]

Expected: [what should happen]
Actual: [what actually happens]

Start by reading [relevant files].
Identify the root cause (don't just fix the symptom).
Write a test that reproduces the bug, then fix it.
Run `php artisan test --filter=[TestName]` to verify.
```

### Add a Service Class

```
Create a new service: app/Services/[Name]Service.php

Reference existing service: app/Services/LicenseService.php for patterns.

The service should:
- [method 1]: [description]
- [method 2]: [description]

Use constructor injection for dependencies.
Add PHPDoc blocks with array shapes where appropriate.
Write unit tests in tests/Unit/Services/[Name]ServiceTest.php.
Run tests and format with Pint.
```

### Refactor Existing Code

```
Don't make changes yet. First, enter Plan Mode.

Analyze [file/class] and create a refactoring plan:
- What are the current responsibilities?
- What should be extracted?
- What's the impact on other files?

After I approve the plan, implement it step by step.
Run the full test suite after each step to catch regressions.
```

---

## 17. Cheat Sheet

### Keyboard Shortcuts

| Shortcut | Action |
|---|---|
| `Shift+Tab` | Toggle Plan Mode |
| `Esc` (x2) | Open rewind menu |
| `Ctrl+C` | Cancel current action |

### Essential Commands

| Command | What It Does |
|---|---|
| `/clear` | Reset conversation (keeps CLAUDE.md) |
| `/compact "focus"` | Summarize conversation, keep focus area |
| `/rewind` | Undo to a previous point |
| `/hooks` | Configure automation hooks |
| `/simplify` | Review code quality |
| `/statusline` | Show context usage |

### CLI Flags

| Flag | What It Does |
|---|---|
| `--continue` | Resume last session |
| `--resume` | Pick a session to resume |
| `--permission-mode plan` | Start in Plan Mode |
| `--worktree <name>` | Start in isolated worktree |
| `-p "prompt"` | Non-interactive single prompt |
| `--output-format json` | Structured output |

### The Workflow

```
1. /clear (start fresh)
2. Write a specific prompt with verification criteria
3. Reference existing code patterns
4. Let Claude implement + test
5. Review the output
6. If wrong twice → /clear + better prompt
7. vendor/bin/pint --dirty (format)
8. php artisan test (verify)
9. Commit when satisfied
```

---

## Final Summary

The three things that matter most:

1. **Be specific** — vague prompts get vague results
2. **Include verification** — tests are your safety net
3. **Manage context** — `/clear` early and often

Master these three and you'll get 90% of the way to maximum output. Everything else (hooks, skills, MCP, worktrees) is optimization on top of a solid foundation.