FROM node:19-slim

WORKDIR /app

## install pnpm
RUN npm i -g pnpm@8.2.0

## copy root config
COPY package.json package.json
COPY tsconfig*.json tsconfig*.json
COPY vitest*.ts vitest*.ts
COPY pnpm-*.yaml pnpm-*.yaml

## copy package config
COPY **/package.json **/package.json
COPY **/tsconfig*.json **/tsconfig*.json
COPY **/vitest*.ts **/vitest*.ts
COPY **/pnpm-*.yaml **/pnpm-*.yaml

## install packages
RUN pnpm install

## copy project
COPY . .

## install packages
RUN pnpm install

## build project
RUN pnpm build
