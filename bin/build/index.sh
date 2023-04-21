#!/bin/bash

pnpm @unaffected/app build
pnpm @unaffected/utility build
pnpm @unaffected/gateway build

pnpm @unaffected/api build
