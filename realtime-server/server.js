const http = require("http");
const { Server } = require("socket.io");

const server = http.createServer((req, res) => {
    res.writeHead(200, { "Content-Type": "text/html; charset=utf-8" });
    res.end("<h1>🚀 Real-time Chat Server is running...</h1>");
});

const io = new Server(server, {
    cors: { origin: "*", methods: ["GET", "POST"] }
});

io.on("connection", (socket) => {
    console.log("🟢 client connected:", socket.id);

    socket.on('join_room', (bookingId) => {
        const roomName = `booking_${bookingId}`;
        socket.join(roomName);
        console.log(`🙋 client ${socket.id} joined room: ${roomName}`);
    });

    socket.on("send_message", (data) => {
        const roomName = `booking_${data.booking_id}`;
        console.log(`📩 message received for room ${roomName}:`, data.message);
        // ส่งข้อความไปให้ทุกคนในห้อง (รวมทั้งตัวเอง)
        io.to(roomName).emit("receive_message", data);
        
        // ส่งการแจ้งเตือน "ข้อความใหม่" ไปยังทุกคนที่ไม่ได้อยู่ในห้องนั้น
        socket.broadcast.emit("new_unread_message", {
            booking_id: data.booking_id
        });
    });

    socket.on("mark_as_read", (data) => {
        const roomName = `booking_${data.booking_id}`;
        console.log(`✅ mark_as_read for room ${roomName}`);
        // ✅ แก้ไขแล้ว: ลบ .broadcast ออก
        socket.to(roomName).emit('messages_have_been_read', { 
            booking_id: data.booking_id 
        });
    });

    socket.on("typing", (data) => {
        const roomName = `booking_${data.booking_id}`;
        // ✅ แก้ไขแล้ว: ลบ .broadcast ออก
        socket.to(roomName).emit("show_typing", data);
    });

    socket.on("stop_typing", (data) => {
        const roomName = `booking_${data.booking_id}`;
        // ✅ แก้ไขแล้ว: ลบ .broadcast ออก
        socket.to(roomName).emit("hide_typing", data);
    });

    socket.on("disconnect", () => {
        console.log("🔴 client disconnected:", socket.id);
    });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`🚀 Server is running on port ${PORT}`);
});