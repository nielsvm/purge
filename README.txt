The pipeline:
1. Queuer (>=1, service): Changes made to content & configuration triggers cache
   tag invalidations, which cause purge instructions.
2. Queue (1, plugin): Stores purge instructions.
3. Processor (>=1, plugin): Takes purge instructions from the queue and sends
   them to the enabled purgers.
4. Purger (>=1, plugin): Performs purge instructions.

A "purge instruction" is currently represented as a Purgeable, of which there
are currently 4 built-in types, but more can be added:
- Tag
- Path
- WildcardPath
- FullDomain
