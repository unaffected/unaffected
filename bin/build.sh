#!/bin/bash

# packages
pnpm @unaffected/app build
pnpm @unaffected/utility build
pnpm @unaffected/command build
pnpm @unaffected/gateway build

# services
pnpm @unaffected/gateway build

# apps
pnpm @unaffected/api build
