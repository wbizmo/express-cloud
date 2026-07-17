# Sprint 3 Report

## Implemented

- shared administrator and staff login route;
- searchable staff-name selector;
- eight-character access-key validation;
- encrypted access-key storage architecture;
- blind-index login matching;
- account status enum;
- account, session, and security-event models;
- MySQL-targeted migrations;
- short-lived request throttling;
- session regeneration and inactivity handling;
- secure logout;
- read-only profile page;
- profile-picture upload and removal;
- authenticated topbar profile and logout integration;
- authentication tests and documentation.

## Deferred

- company, branch, role, and permission assignments;
- administrator account-management interface;
- administrator access-key reveal;
- active-session administration;
- first-run company onboarding;
- production SQL installation record.

These are implemented in later sprints.

## Locked exclusions

- no IP banning;
- no permanent account lockout;
- no email field on login;
- no password field on login;
- no role selector;
- no database service used in Codespaces.
