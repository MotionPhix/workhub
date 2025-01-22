export function getInitials(fullName: string, maxInitials = 2): string {
  if (!fullName) return '';

  return fullName
    .trim()
    .split(/\s+/)  // Handle multiple whitespace characters
    .filter(part => part.length > 0)  // Remove empty parts
    .map(part => part[0]?.toUpperCase() || '')
    .slice(0, maxInitials)
    .join('');
}
