#!/usr/bin/env bash

set -euo pipefail

# This script is used to copy the AI instructions to various files needed for different agents.

AI_INSTRUCTIONS_FILE="ai/instructions.md"
AGENT_FILES=(
    "AGENTS.md"
    "CLAUDE.md"
    "GEMINI.md"
    ".github/copilot-instructions.md"
)
for AGENT_FILE in "${AGENT_FILES[@]}"; do
    cp "$AI_INSTRUCTIONS_FILE" "$AGENT_FILE"
    echo "Copied $AI_INSTRUCTIONS_FILE to $AGENT_FILE"
done