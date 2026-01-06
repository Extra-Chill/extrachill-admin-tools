# QR Code Generator Tool

The QR Code Generator provides a simple interface for generating high-resolution, print-ready QR codes for any URL.

## Overview

This tool centralizes QR code generation for the platform, ensuring consistent quality and simplified access for administrators needing to create physical marketing materials or links.

## React Interface

The tool is implemented as a React component (`QRCodeGenerator.jsx`) within the Admin Tools SPA.

### Features
- **URL Input**: Enter any valid URL to generate a QR code.
- **Real-time Generation**: QR codes are generated on-demand via the REST API.
- **Downloadable Output**: Provides a high-resolution image that can be saved for print or digital use.

## API Integration

The React component interacts with the `extrachill-api` plugin via the following endpoint:

`POST /wp-json/extrachill/v1/tools/qr-code`

### Parameters
- `url` (string): The target URL for the QR code.

## Technical Implementation

The backend utilizes the `endroid/qr-code` library (managed via `extrachill-api`) to generate the QR code images. The Admin Tools interface provides the user-facing wrapper for this functionality.
