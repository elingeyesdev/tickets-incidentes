import json
import sys

def extract_admin_endpoints():
    """Extract all endpoints relevant to PLATFORM_ADMIN role"""

    with open('storage/api-docs/api-docs.json', 'r', encoding='utf-8') as f:
        api_docs = json.load(f)

    paths = api_docs.get('paths', {})
    components = api_docs.get('components', {})

    admin_endpoints = []

    # Define admin-relevant endpoints based on functionality
    admin_keywords = [
        'company-requests',
        '/companies',
        '/users',
        '/roles',
        'PLATFORM_ADMIN',
        'status',
        'approve',
        'reject'
    ]

    for path, methods in paths.items():
        endpoint_data = {
            'path': path,
            'methods': {}
        }

        is_admin_relevant = False

        for method, details in methods.items():
            if method in ['get', 'post', 'put', 'patch', 'delete']:
                # Check if endpoint mentions PLATFORM_ADMIN
                description = details.get('description', '')
                summary = details.get('summary', '')
                tags = details.get('tags', [])

                # Check security requirements
                security = details.get('security', [])

                # Check if it's admin-relevant
                full_text = f"{path} {description} {summary} {str(tags)} {str(security)}".lower()

                if 'platform_admin' in full_text or 'platform admin' in full_text:
                    is_admin_relevant = True

                # Also include key management endpoints
                if any(keyword in path.lower() for keyword in admin_keywords):
                    is_admin_relevant = True

                if is_admin_relevant:
                    endpoint_data['methods'][method] = {
                        'summary': details.get('summary', ''),
                        'description': details.get('description', ''),
                        'tags': details.get('tags', []),
                        'parameters': details.get('parameters', []),
                        'requestBody': details.get('requestBody', {}),
                        'responses': details.get('responses', {}),
                        'security': details.get('security', [])
                    }

        if is_admin_relevant and endpoint_data['methods']:
            admin_endpoints.append(endpoint_data)

    return admin_endpoints

def main():
    endpoints = extract_admin_endpoints()

    print(f"Found {len(endpoints)} admin-relevant endpoints\n")
    print("=" * 80)

    # Group by category
    categories = {
        'Authentication': [],
        'Company Management': [],
        'Company Requests': [],
        'User Management': [],
        'Role Management': [],
        'Content Management': [],
        'Announcements': [],
        'Help Center': []
    }

    for endpoint in endpoints:
        path = endpoint['path']

        if '/auth/' in path:
            categories['Authentication'].append(endpoint)
        elif '/company-requests' in path:
            categories['Company Requests'].append(endpoint)
        elif '/companies' in path:
            categories['Company Management'].append(endpoint)
        elif '/users' in path and '/roles' not in path:
            categories['User Management'].append(endpoint)
        elif '/roles' in path or '/users/' in path and '/roles' in path:
            categories['Role Management'].append(endpoint)
        elif '/announcements' in path:
            categories['Announcements'].append(endpoint)
        elif '/help-center' in path:
            categories['Help Center'].append(endpoint)
        else:
            categories['Content Management'].append(endpoint)

    # Print summary
    for category, endpoints_list in categories.items():
        if endpoints_list:
            print(f"\n{category}: {len(endpoints_list)} endpoints")
            for endpoint in endpoints_list:
                methods = ', '.join(endpoint['methods'].keys()).upper()
                print(f"  [{methods}] {endpoint['path']}")

    # Write detailed report
    with open('REPORTE_endpoints_admin_data.json', 'w', encoding='utf-8') as f:
        json.dump({
            'categories': categories,
            'total_endpoints': len(endpoints)
        }, f, indent=2, ensure_ascii=False)

    print(f"\n\n{'=' * 80}")
    print(f"Detailed data saved to: REPORTE_endpoints_admin_data.json")

if __name__ == '__main__':
    main()
