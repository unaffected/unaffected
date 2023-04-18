#!/bin/bash

# app
pnpm @unaffected/app build

# packages
pnpm @unaffected/utility build
pnpm @unaffected/command build
