"use client"

import { useState, useEffect, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Send, ArrowLeft, MessageCircle } from "lucide-react"

interface Message {
  sender: string
  receiver: string
  message: string
  timestamp: string
}

interface AppUser {
  username: string
  name: string
  role: string
}

export default function MessagingSystem() {
  const [currentUser, setCurrentUser] = useState<AppUser | null>(null)
  const [senders, setSenders] = useState<string[]>([])
  const [selectedUser, setSelectedUser] = useState<string | null>(null)
  const [chatMessages, setChatMessages] = useState<Message[]>([])
  const [newMessage, setNewMessage] = useState("")
  const [loading, setLoading] = useState(false)
  const [users, setUsers] = useState<AppUser[]>([])
  const messagesEndRef = useRef<HTMLDivElement>(null)

  // Mock current user - in real app, get from session/auth
  useEffect(() => {
    // Simulate getting current user from session
    const mockCurrentUser = {
      username: "kel",
      name: "Michael Santos",
      role: "staff",
    }
    setCurrentUser(mockCurrentUser)

    // Load users from accounts data
    loadUsers()
  }, [])

  const loadUsers = async () => {
    try {
      // In real implementation, this would be from your accounts system
      const mockUsers: AppUser[] = [
        { username: "admin", name: "System Administrator", role: "admin" },
        { username: "elena.mendoza", name: "Elena Garcia Mendoza", role: "staff" },
        { username: "roberto.villanueva", name: "Roberto Santos Villanueva", role: "staff" },
        { username: "carmen.reyes", name: "Carmen Torres Reyes", role: "staff" },
        { username: "michael.anderson", name: "Michael John Anderson", role: "volunteer" },
      ]
      setUsers(mockUsers)
    } catch (error) {
      console.error("Error loading users:", error)
    }
  }

  const loadInbox = async () => {
    if (!currentUser) return

    try {
      const response = await fetch(`get-inbox.php?user=${currentUser.username}`)
      const data = await response.json()
      setSenders(data)
    } catch (error) {
      console.error("Error loading inbox:", error)
    }
  }

  const loadChat = async (otherUser: string) => {
    if (!currentUser) return

    setLoading(true)
    try {
      const response = await fetch(`get-chat.php?user1=${currentUser.username}&user2=${otherUser}`)
      const data = await response.json()
      setChatMessages(data)
      setSelectedUser(otherUser)
    } catch (error) {
      console.error("Error loading chat:", error)
    } finally {
      setLoading(false)
    }
  }

  const sendMessage = async () => {
    if (!currentUser || !selectedUser || !newMessage.trim()) return

    setLoading(true)
    try {
      const response = await fetch("send-message.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          sender: currentUser.username,
          receiver: selectedUser,
          message: newMessage.trim(),
        }),
      })

      const result = await response.json()

      if (result.success) {
        setNewMessage("")
        // Reload chat to show new message
        await loadChat(selectedUser)
      } else {
        alert("Error sending message: " + result.error)
      }
    } catch (error) {
      console.error("Error sending message:", error)
      alert("Error sending message")
    } finally {
      setLoading(false)
    }
  }

  const getUserName = (username: string) => {
    const user = users.find((u) => u.username === username)
    return user ? user.name : username
  }

  const getUserInitials = (username: string) => {
    const name = getUserName(username)
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
  }

  const formatTime = (timestamp: string) => {
    const date = new Date(timestamp)
    return date.toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    })
  }

  const formatDate = (timestamp: string) => {
    const date = new Date(timestamp)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)

    if (date.toDateString() === today.toDateString()) {
      return "Today"
    } else if (date.toDateString() === yesterday.toDateString()) {
      return "Yesterday"
    } else {
      return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
      })
    }
  }

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }, [chatMessages])

  // Load inbox on component mount
  useEffect(() => {
    if (currentUser) {
      loadInbox()
    }
  }, [currentUser])

  if (!currentUser) {
    return <div className="flex items-center justify-center h-64">Loading...</div>
  }

  return (
    <div className="max-w-6xl mx-auto p-4">
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[600px]">
        {/* Inbox/Contacts Panel */}
        <Card className="lg:col-span-1">
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2">
              <MessageCircle className="h-5 w-5" />
              Messages
            </CardTitle>
            <Button onClick={loadInbox} variant="outline" size="sm" className="w-full bg-transparent">
              Refresh Inbox
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            <ScrollArea className="h-[480px]">
              {senders.length === 0 ? (
                <div className="p-4 text-center text-gray-500">
                  <MessageCircle className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                  <p>No messages yet</p>
                </div>
              ) : (
                <div className="space-y-1">
                  {senders.map((sender) => (
                    <div
                      key={sender}
                      onClick={() => loadChat(sender)}
                      className={`flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b ${
                        selectedUser === sender ? "bg-blue-50 border-blue-200" : ""
                      }`}
                    >
                      <Avatar className="h-10 w-10">
                        <AvatarFallback className="bg-blue-100 text-blue-600">{getUserInitials(sender)}</AvatarFallback>
                      </Avatar>
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-sm truncate">{getUserName(sender)}</p>
                        <p className="text-xs text-gray-500">@{sender}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </ScrollArea>
          </CardContent>
        </Card>

        {/* Chat Panel */}
        <Card className="lg:col-span-2">
          {selectedUser ? (
            <>
              <CardHeader className="pb-3 border-b">
                <div className="flex items-center gap-3">
                  <Button variant="ghost" size="sm" onClick={() => setSelectedUser(null)} className="lg:hidden">
                    <ArrowLeft className="h-4 w-4" />
                  </Button>
                  <Avatar className="h-8 w-8">
                    <AvatarFallback className="bg-green-100 text-green-600">
                      {getUserInitials(selectedUser)}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <h3 className="font-semibold">{getUserName(selectedUser)}</h3>
                    <p className="text-sm text-gray-500">@{selectedUser}</p>
                  </div>
                </div>
              </CardHeader>

              <CardContent className="p-0 flex flex-col h-[480px]">
                {/* Messages Area */}
                <ScrollArea className="flex-1 p-4">
                  {loading && chatMessages.length === 0 ? (
                    <div className="flex items-center justify-center h-32">
                      <div className="text-gray-500">Loading messages...</div>
                    </div>
                  ) : chatMessages.length === 0 ? (
                    <div className="flex items-center justify-center h-32">
                      <div className="text-center text-gray-500">
                        <MessageCircle className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                        <p>No messages yet. Start the conversation!</p>
                      </div>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {chatMessages.map((msg, index) => {
                        const isCurrentUser = msg.sender === currentUser.username
                        const showDate =
                          index === 0 || formatDate(msg.timestamp) !== formatDate(chatMessages[index - 1].timestamp)

                        return (
                          <div key={index}>
                            {showDate && (
                              <div className="text-center text-xs text-gray-500 my-4">{formatDate(msg.timestamp)}</div>
                            )}
                            <div className={`flex ${isCurrentUser ? "justify-end" : "justify-start"}`}>
                              <div className={`max-w-[70%] ${isCurrentUser ? "order-2" : "order-1"}`}>
                                <div
                                  className={`px-4 py-2 rounded-lg ${
                                    isCurrentUser ? "bg-blue-600 text-white" : "bg-gray-100 text-gray-900"
                                  }`}
                                >
                                  <p className="text-sm">{msg.message}</p>
                                </div>
                                <p
                                  className={`text-xs text-gray-500 mt-1 ${isCurrentUser ? "text-right" : "text-left"}`}
                                >
                                  {formatTime(msg.timestamp)}
                                </p>
                              </div>
                              {!isCurrentUser && (
                                <Avatar className="h-6 w-6 order-1 mr-2 mt-1">
                                  <AvatarFallback className="bg-gray-200 text-gray-600 text-xs">
                                    {getUserInitials(msg.sender)}
                                  </AvatarFallback>
                                </Avatar>
                              )}
                            </div>
                          </div>
                        )
                      })}
                      <div ref={messagesEndRef} />
                    </div>
                  )}
                </ScrollArea>

                {/* Message Input */}
                <div className="border-t p-4">
                  <div className="flex gap-2">
                    <Input
                      value={newMessage}
                      onChange={(e) => setNewMessage(e.target.value)}
                      placeholder="Type your message..."
                      onKeyPress={(e) => {
                        if (e.key === "Enter" && !e.shiftKey) {
                          e.preventDefault()
                          sendMessage()
                        }
                      }}
                      disabled={loading}
                      className="flex-1"
                    />
                    <Button onClick={sendMessage} disabled={loading || !newMessage.trim()} size="sm">
                      <Send className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </>
          ) : (
            <CardContent className="flex items-center justify-center h-full">
              <div className="text-center text-gray-500">
                <Avatar className="h-16 w-16 mx-auto mb-4">
                  <AvatarFallback className="bg-gray-200 text-gray-600 text-2xl">U</AvatarFallback>
                </Avatar>
                <h3 className="text-lg font-medium mb-2">Select a conversation</h3>
                <p>Choose someone from your inbox to start messaging</p>
              </div>
            </CardContent>
          )}
        </Card>
      </div>
    </div>
  )
}
