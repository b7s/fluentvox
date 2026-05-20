.PHONY: help install test test-unit test-types test-coverage test-plugin catraca release

RELEASE_VERSION := $(if $(VERSION),$(VERSION),$(version))
RELEASE_MESSAGE := $(if $(MESSAGE),$(MESSAGE),$(message))

# Default target
help:
	@echo "Available commands:"
	@echo "  make install         - Install dependencies"
	@echo "  make test            - Run all tests"
	@echo "  make test-unit       - Run unit tests only"
	@echo "  make test-types      - Run PHPStan static analysis"
	@echo "  make test-coverage   - Run tests with coverage report"
	@echo "  make test-plugin     - Test Composer plugin in fresh project"
	@echo "  make catraca         - Run catraca quality gates"
	@echo "  make release version=x.y.z message='msg' - Run tests and create Git tag"
	@echo "  make clean           - Clean cache and temporary files"

# Install dependencies
install:
	@echo "📦 Installing dependencies..."
	composer install
	@echo "✅ Installation complete!"

# Run all tests
test:
	@echo "🧪 Running all tests..."
	composer test

# Run unit tests only
test-unit:
	@echo "🧪 Running unit tests..."
	composer test:unit

# Run PHPStan static analysis
test-types:
	@echo "🔍 Running PHPStan static analysis..."
	composer test:types

# Run type coverage check
test-type-coverage:
	@echo "📊 Running type coverage check..."
	composer test:type-coverage

# Run tests with coverage
test-coverage:
	@echo "📊 Running tests with coverage..."
	composer test:coverage

# Test Composer plugin in fresh project
test-plugin:
	@echo "🔌 Testing Composer plugin..."
	./test-plugin.sh

# Run catraca quality gates
catraca:
	@echo "🚧 Running catraca quality gates..."
	./vendor/bin/catraca

# Create a tagged release (auto-commits changes if any)
release:
	@if [ -f version ]; then \
		LAST_VERSION=$$(cat version); \
		echo "📌 Last version: v$$LAST_VERSION"; \
		echo ""; \
	fi; \
	VERSION_INPUT="$(RELEASE_VERSION)"; \
	if [ -z "$$VERSION_INPUT" ]; then \
		read -p "Enter release version (format x.y.z): " VERSION_INPUT; \
	fi; \
	if ! echo "$$VERSION_INPUT" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$$'; then \
		echo "❌ Invalid version format. Expected x.y.z (e.g., 1.3.0)"; exit 1; \
	fi; \
	echo "📦 New version: v$$VERSION_INPUT"; \
	MESSAGE_INPUT="$(RELEASE_MESSAGE)"; \
	if [ -z "$$MESSAGE_INPUT" ]; then \
		echo "Enter release message (press Enter for default, Ctrl+D when done for multi-line):"; \
		MESSAGE_INPUT=$$(cat); \
		if [ -z "$$MESSAGE_INPUT" ]; then \
			MESSAGE_INPUT="Release v$$VERSION_INPUT"; \
		fi; \
	fi; \
	echo "🧪 Running full test suite..."; \
	if ! composer test; then \
		echo "❌ Tests failed. Fix issues before releasing."; \
		exit 1; \
	fi; \
	echo "🚧 Running catraca quality gates..."; \
	if ! ./vendor/bin/catraca; then \
		echo "❌ Catraca quality gates failed. Fix issues before releasing."; \
		exit 1; \
	fi; \
	echo "🔍 Checking for uncommitted changes..."; \
	if ! git diff --quiet || ! git diff --cached --quiet; then \
		echo "📝 Found uncommitted changes. Staging files..."; \
		git add -A; \
		echo "💾 Creating commit..."; \
		git commit -m "$$MESSAGE_INPUT" || true; \
	else \
		echo "✅ Working tree is clean."; \
	fi; \
	echo "🚀 Pushing commits to origin..."; \
	if git push origin HEAD; then \
		echo "✅ Push successful!"; \
	else \
		echo "⚠️  No commits to push (working tree was clean)"; \
	fi; \
	echo "🏷️  Creating tag v$$VERSION_INPUT..."; \
	git tag -a v$$VERSION_INPUT -m "$$MESSAGE_INPUT"; \
	echo "🚀 Pushing tag to origin..."; \
	if git push origin v$$VERSION_INPUT; then \
		echo "✅ Tag pushed successfully!"; \
		echo "📝 Updating version file..."; \
		echo "$$VERSION_INPUT" > version; \
		git add version; \
		git commit -m "Update version to $$VERSION_INPUT" || true; \
		git push origin HEAD || true; \
	else \
		echo "❌ Failed to push tag"; \
		exit 1; \
	fi; \
	echo ""; \
	echo "✅ Release v$$VERSION_INPUT created successfully!"; \
	echo "📦 Packagist will automatically detect the new version."; \
	echo "🔗 View release: https://github.com/b7s/fluentvox/releases/tag/v$$VERSION_INPUT"

# Clean cache and temporary files
clean:
	@echo "🧹 Cleaning cache and temporary files..."
	rm -rf build/
	rm -rf vendor/
	rm -rf .phpunit.cache/
	@echo "✅ Clean complete!"
