#!/bin/bash

pnpm @unaffected/app build:watch & \
pnpm @unaffected/utility build:watch & \
pnpm @unaffected/gateway build:watch && \
pnpm @unaffected/renderer build:watch && \
fg
