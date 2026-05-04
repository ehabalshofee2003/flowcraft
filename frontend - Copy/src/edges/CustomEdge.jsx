import React from 'react';
import { BaseEdge, getBezierPath, EdgeLabelRenderer } from 'reactflow';

export default function CustomEdge({
  id,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  sourceHandle, // هذا هو المفتاح! سنقرأ منه اسم المخرج
  style = {},
  markerEnd,
}) {
  // حساب مسار السلك
  const [edgePath, labelX, labelY] = getBezierPath({
    sourceX,
    sourceY,
    sourcePosition,
    targetX,
    targetY,
    targetPosition,
  });

  // تحديد اللون والتسمية بناءً على اسم الـ Handle
  let edgeColor = '#64748b'; // لون رمادي افتراضي
  let label = '';

  if (sourceHandle === 'true') {
    edgeColor = '#22c55e'; // أخضر
    label = 'True';
  } else if (sourceHandle === 'false') {
    edgeColor = '#ef4444'; // أحمر
    label = 'False';
  }

  return (
    <>
      {/* رسم السلك الأساسي */}
      <BaseEdge
        id={id}
        path={edgePath}
        markerEnd={markerEnd}
        style={{ 
          ...style, 
          stroke: edgeColor, 
          strokeWidth: 2 
        }}
      />

      {/* رسم التسمية في منتصف السلك (فقط إذا كان هناك تسمية) */}
      {label && (
        <EdgeLabelRenderer>
          <div
            style={{
              position: 'absolute',
              transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
              pointerEvents: 'all', // مهم لتجنب اخفاء السلك تحته
            }}
            className="nodrag nopan"
          >
            <span
              style={{
                backgroundColor: edgeColor,
                color: 'white',
                fontSize: '10px',
                fontWeight: 'bold',
                padding: '2px 6px',
                borderRadius: '4px',
                boxShadow: '0 1px 2px rgba(0,0,0,0.1)'
              }}
            >
              {label}
            </span>
          </div>
        </EdgeLabelRenderer>
      )}
    </>
  );
}