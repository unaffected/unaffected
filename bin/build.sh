#!/bin/bash

# app
pnpm @unaffected/app build

# packages
pnpm @unaffected/timer build
pnpm @unaffected/command build
