---
name: developer-docs
description: >-
    Writes and maintains developer-facing documentation. Activates when creating,
    updating, or organizing technical documentation; writing API docs, feature guides,
    or architecture docs; or when the user mentions docs, documentation, guide,
    or developer guide.
---

# Developer Documentation Writing

## When to Apply

Activate this skill when:

- Creating new developer documentation
- Updating existing documentation
- Organizing or restructuring docs
- Writing API docs, feature guides, or architecture docs

## Documentation Structure

All developer documentation lives in the `docs/` directory:

```
docs/
├── frontend/    # Frontend-related documentation

├── backend/     # Backend-related documentation

├── product/     # Product-level documentation

└── ...          # Other topic-based directories

```

### Directory Organization

- **Frontend docs**: Place in `docs/frontend/` (React components, state management, routing, etc.)
- **Backend docs**: Place in `docs/backend/` (API endpoints, services, database, etc.)
- **Product docs**: Place in `docs/product/` (architecture, domain concepts, user-facing features)
- **Group by feature or domain**: Create subdirectories for related topics (e.g., `docs/backend/systems/`,
  `docs/frontend/components/`)

## Page Structure

Every documentation page must follow this exact structure, in order:

### 1. Title (H1)

Exactly one `#` heading per page. Concise, noun-based. No version numbers or prefixes.

```markdown

# SSH

```

```markdown

# Dialog Steps

```

### 2. Table of Contents

Immediately after the title (or a brief one-line description), add a nested unordered list of anchor links. Use `-`
(dash) as the bullet character. Nest subsections with 4-space indentation. Every H2 and H3 must appear in the TOC:

```markdown
- [Introduction](#introduction)
- [Basic Usage](#basic-usage)
- [Configuration](#configuration)
    - [Timeout](#timeout)
    - [Working Directory](#working-directory)
- [Testing](#testing)
```

### 3. Anchored Headings

Place an `<a name="..."></a>` anchor tag on the line immediately before each heading, with one blank line above the
anchor and one blank line between the anchor and the heading:

```markdown
<a name="basic-usage"></a>

## Basic Usage

```

Anchor IDs use a lowercase kebab-case and must match the TOC link fragment.

### 4. Introduction Section

Every page starts with an `## Introduction` section that follows this formula:

1. **What it is** — a one-sentence definition
2. **Why it matters** — an approachable explanation (1-2 sentences)
3. **What this page covers** — optional roadmap sentence or key features list

The introduction may include a short code example showing the most common use case. Keep it to 2-4 short paragraphs
maximum.

```markdown
<a name="introduction"></a>

## Introduction

The SSH facade provides a fluent interface for executing commands on remote servers. It supports password and key-based
authentication, synchronous and queued execution, streaming output, and comprehensive testing utilities.
```

When the feature has multiple key capabilities, list them with bold labels:

```markdown
Key features:

- **Automatic completion tracking**: Steps are marked as completed when visited and navigated away from
- **Flexible validation**: Configure validation per-step with auto-detection or explicit field lists
```

### 5. Section Order

Follow this predictable order (skip sections that do not apply):

1. **Introduction** — what, why, and overview
2. **Basic Usage** — the simplest working example
3. **Configuration** — adjusting defaults and options
4. **Advanced Usage** — complex use cases and integrations
5. **Testing** — how to test the feature (fakes, assertions)
6. **Exceptions / Errors** — error types and handling
7. **API Reference** — method signatures and types (if applicable)

## Voice and Tone

### Person

Use the second person throughout — "you may", "you should", "you can". Never use first-person singular. Occasionally use
first-person plural for collaborative framing: "Let's create", "We'll explore".

### No Contractions

Always write "do not" instead of "don't", "you will" instead of "you'll", "it is" instead of "it's". This is a
consistent stylistic choice across all documentation.

### Active Voice

Use active voice. Write, "Laravel dispatches the event" rather than "the event is dispatched by Laravel".

### Characteristic Phrases

Use these natural transition phrases:

- "You may use..." (offering an option)
- "If you would like to..." (introducing optional features)
- "For convenience, you may..." (highlighting shortcuts)
- "Of course, you may also..." (showing alternatives)
- "Sometimes you may wish to..." (introducing edge cases)
- "By default, ..." (documenting default behavior before customization)
- "To get started, ..." (beginning a tutorial flow)
- "For example, ..." (transitioning to code)

### Be Concise

- Paragraphs are 1-4 sentences, rarely exceeding 80 words
- Single-sentence paragraphs are fine for transitions
- Get straight to the point — no filler or preamble

## Code Examples

### Format

Always use fenced code blocks with a language identifier:

- `` ```php `` — PHP code
- `` ```tsx `` — React/TypeScript JSX
- `` ```shell `` — Terminal/Artisan commands
- `` ```blade `` — Blade templates

### Full Context

Code examples must include all relevant context — imports, `use` statements, and namespace declarations when showing
class definitions. Examples should be copy-pasteable:

```php
use App\Facades\Ssh;

$result = Ssh::host('192.168.1.100')
    ->login('deploy', 'secret')
    ->exec('uptime');
```

```tsx
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {InputError} from '@/components/common/input-error';

<div className="flex flex-col gap-2">
    <Label htmlFor="title">Title</Label>
    <Input id="title" name="title" placeholder="My Server"/>
    <InputError message={errors.title}/>
</div>
```

### Abbreviated Classes

When showing a class where only part matters, use `// ...` for omitted sections:

```php
class Flight extends Model
{
    // ...
}
```

### Realistic Values

Use realistic variable names, domain-appropriate values, and concrete examples. Never use abstract placeholders:

```php
// Good: realistic and domain-appropriate
$result = Ssh::host('server.example.com')
    ->login('deploy', 'secret-password')
    ->exec('whoami');

// Bad: abstract placeholders
$thing = Model::find($id);
$thing->doSomething($param);
```

### Transitions to Code

End the sentence before a code block with a colon:

```markdown
Connect to a host and execute a command:
```

### Progressive Complexity

Within a section, start with the simplest example and build up. Show basic usage first, then variations, then
advanced/edge-case usage.

## Callouts and Admonitions

Use only two callout types — `NOTE` and `WARNING`. Use them sparingly (3-8 per page maximum). They are blockquotes with
a special prefix:

```markdown
> [!NOTE]
> Before getting started, be sure to configure a database connection in your application's
> `config/database.php` configuration file.
```

```markdown
> [!WARNING]
> When issuing a mass update via Eloquent, the `saving`, `saved`, `updating`, and `updated` model events
> will not be fired for the updated models.
```

### When to Use Each

**NOTE** — prerequisites, non-obvious behavior, references to related docs, "remember..." reminders.

**WARNING** — data loss risks, unexpected side effects, security considerations, performance pitfalls.

Do not use `TIP`, `IMPORTANT`, `CAUTION`, or any other callout variant. Never use callouts for basic information that
belongs in normal prose.

## Text Formatting

### Inline Code (backticks)

Use for:

- Class names: `` `App\Models\Flight` ``
- Method names: `` `find` ``, `` `save` ``
- Property/prop names: `` `$fillable` ``, `` `canGotoNext` ``
- Configuration keys: `` `config/queue.php` ``
- File paths: `` `routes/web.php` ``
- Environment variables: `` `QUEUE_CONNECTION` ``
- CLI commands inline: `` `php artisan make:model` ``
- Database column/table names: `` `flights` ``, `` `created_at` ``
- Facade names: `` `Gate` ``, `` `Route` ``

### Bold

Use for:

- Concept labels on first introduction: "A **form group** is the fundamental unit"
- Key terms in explanations: "Each server runs in its own **Docker container**"
- Sub-section labels that do not warrant a heading

### Italic

Use extremely rarely. Prefer bold or inline code for emphasis.

## Tables

Use tables for:

- Component/prop reference lists with Type and Description columns
- Method/exception reference with concise descriptions
- Spacing/configuration reference with short values

Do not use tables for:

- Explaining concepts (use prose)
- Listing steps (use ordered lists)
- Anything with long descriptions that would wrap awkwardly

Standard format:

```markdown
| Prop          | Type      | Description                          |
|---------------|-----------|--------------------------------------|
| `name`        | `string`  | Unique identifier (required)         |
| `label`       | `string`  | Display title                        |
| `disabled`    | `boolean` | Prevents navigation to step          |
```

## Lists

- Use `-` (dash) as the bullet character for unordered lists
- Use numbered lists (`1.`, `2.`) only for sequential steps
- Nest with 4-space indentation

## Cross-References

Link to other documentation pages inline where the referenced concept is naturally mentioned. Do not collect links into
"See Also" sections:

```markdown
For more details, see the [SSH documentation](/docs/backend/ssh.md).
```

For same-page references, use anchor links:

```markdown
See [Validation](#validation) below.
```

## Parameters and Methods

Describe parameters in the surrounding prose, not in standalone tables (unless listing many props for a component):

```markdown
The first argument passed to the `chunk` method is the number of records you wish to receive per "chunk".
```

Show method signatures in code when the full signature is useful:

```php
Route::redirect($uri, $destination, $statusCode = 302);
```

Document default values inline: "By default, `Route::redirect` returns a `302` status code."

## Happy Path vs. Edge Cases

Always present in this order:

1. **Default/common usage** — simplest code example first
2. **Customization** — "If you would like to customize..." or "You may also..."
3. **Edge cases** — "In some cases...", "Sometimes you may wish to..."
4. **Warnings** — `> [!WARNING]` blocks for pitfalls

Place edge cases in their own `###` or `####` subsections to keep the main flow clean.

## Artisan Commands

Show terminal commands in `shell` code blocks:

```shell
php artisan make:notification InvoicePaid
```

Explain flags/options in the prose following the command, not in a separate table.

## Common Pitfalls

- Placing docs outside the `docs/` directory structure
- Writing verbose introductions instead of getting to the point
- Using abstract placeholders instead of realistic examples
- Forgetting to group related docs in subdirectories
- Using the first or third person instead of the second person ("you")
- Using contractions ("don't", "can't") instead of full forms ("do not", "cannot")
- Adding `TIP` or `CAUTION` callouts — only `NOTE` and `WARNING` are allowed
- Forgetting the `<a name="">` anchor before headings
- Omitting language identifiers on code fences
- Writing code examples without imports or context
- Using tables for conceptual explanations instead of prose