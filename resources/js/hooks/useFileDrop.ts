import { useCallback, useEffect, useState } from 'react';

/**
 * Tracks whether a file is being dragged over the browser window and
 * provides a handler to attach to a specific drop zone.
 *
 * `isDraggingOverWindow` turns true the moment a file enters the viewport
 * and false when it leaves or is dropped. A nested-enter counter prevents
 * the state from flickering as the cursor moves between child elements.
 */
export function useFileDrop(onFile: (file: File) => void) {
    const [isDraggingOverWindow, setIsDraggingOverWindow] = useState(false);

    useEffect(() => {
        let enterCount = 0;

        const hasFiles = (event: DragEvent) =>
            event.dataTransfer?.types.includes('Files') ?? false;

        const handleDragEnter = (event: DragEvent) => {
            if (!hasFiles(event)) return;
            event.preventDefault();
            enterCount++;
            if (enterCount === 1) setIsDraggingOverWindow(true);
        };

        const handleDragLeave = (event: DragEvent) => {
            if (!hasFiles(event)) return;
            event.preventDefault();
            enterCount--;
            if (enterCount === 0) setIsDraggingOverWindow(false);
        };

        const handleDragOver = (event: DragEvent) => {
            if (!hasFiles(event)) return;
            event.preventDefault();
        };

        const handleDrop = () => {
            enterCount = 0;
            setIsDraggingOverWindow(false);
        };

        window.addEventListener('dragenter', handleDragEnter);
        window.addEventListener('dragleave', handleDragLeave);
        window.addEventListener('dragover', handleDragOver);
        window.addEventListener('drop', handleDrop);

        return () => {
            window.removeEventListener('dragenter', handleDragEnter);
            window.removeEventListener('dragleave', handleDragLeave);
            window.removeEventListener('dragover', handleDragOver);
            window.removeEventListener('drop', handleDrop);
        };
    }, []);

    const dropZoneProps = {
        onDragOver: useCallback((event: React.DragEvent) => {
            event.preventDefault();
            event.stopPropagation();
        }, []),

        onDrop: useCallback(
            (event: React.DragEvent) => {
                event.preventDefault();
                event.stopPropagation();
                const file = event.dataTransfer.files?.[0];
                if (file) onFile(file);
            },
            [onFile],
        ),
    };

    return { isDraggingOverWindow, dropZoneProps };
}
