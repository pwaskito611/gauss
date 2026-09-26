# Design Decisions

## Why Number?

Because arithmetic must have a stable foundation. Gauss uses `Number` as the core representation so that the rest of the library can operate on a consistent, explicit, and semantically meaningful value type.

## Why immutable?

Immutability keeps expressions predictable. A mathematical expression is easier to reason about when operations return a new value rather than mutating an existing one.

## Why explicit operations?

The code should read like the math. Methods such as `add()`, `mul()`, and `compare()` make the intent visible in the source itself.

## Why no framework-level abstraction?

Gauss is a mathematical toolkit, not an application framework. The library is designed around reusable primitives and explicit composition rather than hidden application behaviour.

## Why reusable primitives?

The library roots itself in a compositional philosophy: if a user wants to build a custom model, the building blocks should already exist in a mathematically coherent and inspectable form.

## Why not oversell exactness?

The implementation is honest about whether a function is exact, precise, or approximate. This avoids misleading users about numerical guarantees while preserving the practical usefulness of the library.
