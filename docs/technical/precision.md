# Precision

Gauss separates three different ideas that are often conflated:

## 1. Exact arithmetic

Exact arithmetic is the domain of operations based on decimal-string values and controlled arithmetic rules. `Number` is the primary example of this principle in Gauss.

## 2. Arbitrary-precision decimal arithmetic

This is the concrete implementation strategy used by `Number`: the library normalizes and stores values as decimal strings and applies BCMath operations where appropriate. This helps preserve precision even when repeated computations would be unstable with native floats.

## 3. Numerical approximation

Some operations and algorithms are inherently approximate. This is especially true for iterative or transcendental procedures such as root-finding or computational approximations in some distribution or optimization workflows.

Gauss documents the distinction plainly. A `Number` can be precise as a value; a method may still produce an approximate result when the mathematics itself is approximate.

## Practical implication

The library should be read as a precision-oriented numerics toolkit, not as a promise that every function is mathematically exact under all circumstances.
