#!/bin/bash

# app
pnpm @unaffected/app build

# packages
pnpm @unaffected/command build
