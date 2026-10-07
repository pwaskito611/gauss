# Module Dependencies

Gauss is organized by domain, but the most important real dependency is its numeric foundation.

## Direct internal dependencies

The table lists the modules each module directly imports from `src/`. Dependencies within the same module are omitted.

| Module | Direct dependencies |
| --- | --- |
| `Number` | None |
| `Algebra` | `Number` |
| `Linear` | `Number`, `Algebra` |
| `Geometry` | `Number`, `Linear` |
| `Probability` | `Number` |
| `Distribution` | `Number`, `Probability` |
| `Statistics` | `Number`, `Linear` |
| `Numerical` | `Number` |
| `Optimization` | `Number`, `Linear` |
| `TimeSeries` | `Number`, `Linear`, `Statistics` |
| `Discrete` | `Number` |

## Dependency interpretation

`Number` is the common numeric foundation, but the modules do not form one linear stack. For example, `Geometry` uses vectors from `Linear`, and `TimeSeries` uses both `Statistics` and `Linear`. These are direct source-level imports; they do not describe runtime call order or every conceptual relationship.
