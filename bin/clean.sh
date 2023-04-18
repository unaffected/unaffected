#!/bin/sh

# root project
rm -rf .pnpm-store
rm -rf .DS_STORE

rm -rf dist
rm -rf coverage
rm -rf node_modules
rm -rf types
rm -rf *.tsbuildinfo

rm -rf package/**/dist
rm -rf package/**/coverage
rm -rf package/**/node_modules
rm -rf package/**/types
rm -rf package/**/*.tsbuildinfo

