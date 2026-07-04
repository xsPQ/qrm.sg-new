---
name: docker-delivery
description: Design, modify, and verify secure Dockerfiles, Compose services, image builds, runtime configuration, healthchecks, and container delivery boundaries. Use when a DevAgency task involves Docker, Compose, containerized development/testing, release images, registries, ports, volumes, environment variables, or container health.
---

# Docker Delivery

1. Read Docker, security and release rules plus task scope.
2. Confirm whether Docker serves development, tests, release or production.
3. Use minimal reproducible builds, deliberate base pins, cache-aware stages and non-root runtime where supported.
4. Maintain `.dockerignore`; exclude secrets, `.env`, VCS, local data and generated output.
5. Document ports, networks, volumes, safe `.env.example` variables and health semantics.
6. Run Compose config, build, start, health/smoke and controlled cleanup.

Production requires approval, immutable image identity and rollback. Return commands/results, tag/digest, exposed interfaces, secret-context check and health evidence.
